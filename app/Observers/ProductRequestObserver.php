<?php

namespace App\Observers;

use App\Models\ProductRequest;
use App\Models\SparePart;
use App\Models\InventoryAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductRequestObserver
{
    /**
     * Handle the ProductRequest "updated" event.
     * 
     * This method automatically updates inventory when a product request
     * status changes to or from 'received'.
     */
    public function updated(ProductRequest $productRequest): void
    {
        // Only process if status has changed
        if (!$productRequest->isDirty('status')) {
            return;
        }

        $oldStatus = $productRequest->getOriginal('status');
        $newStatus = $productRequest->status;

        // Case 1: Status changed TO 'received' - increase stock
        if ($newStatus === 'received' && $oldStatus !== 'received') {
            $this->increaseInventory($productRequest);
        }

        // Case 2: Status changed FROM 'received' to something else - reverse the stock increase
        if ($oldStatus === 'received' && $newStatus !== 'received') {
            $this->decreaseInventory($productRequest);
        }
    }

    /**
     * Increase inventory when product request is marked as received.
     */
    protected function increaseInventory(ProductRequest $productRequest): void
    {
        try {
            DB::transaction(function () use ($productRequest) {
                // Check for duplicate updates by looking for existing audit entries
                $existingAudit = InventoryAudit::byReference(
                    ProductRequest::class,
                    $productRequest->id
                )
                ->byChangeType(InventoryAudit::CHANGE_TYPE_PRODUCT_REQUEST_RECEIVED)
                ->where('created_at', '>=', now()->subMinutes(5)) // Within last 5 minutes
                ->exists();

                if ($existingAudit) {
                    Log::warning("Duplicate inventory update prevented for ProductRequest #{$productRequest->id}");
                    return;
                }

                // Get the spare part and lock it for update
                $sparePart = SparePart::lockForUpdate()->findOrFail($productRequest->spare_part_id);
                
                $previousStock = $sparePart->stock_quantity;
                $quantityToAdd = (int) $productRequest->quantity_requested;
                
                // Update stock
                $sparePart->stock_quantity += $quantityToAdd;
                $sparePart->save();
                
                $newStock = $sparePart->stock_quantity;

                // Create audit trail
                InventoryAudit::createForProductRequestReceived(
                    $productRequest,
                    $previousStock,
                    $newStock,
                    auth()->id()
                );

                Log::info("Inventory increased for ProductRequest #{$productRequest->id}: Part #{$sparePart->id} stock changed from {$previousStock} to {$newStock}");
            });
        } catch (\Exception $e) {
            Log::error("Failed to increase inventory for ProductRequest #{$productRequest->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Decrease inventory when product request status changes from received.
     */
    protected function decreaseInventory(ProductRequest $productRequest): void
    {
        try {
            DB::transaction(function () use ($productRequest) {
                // Check for duplicate reversals
                $existingReversal = InventoryAudit::byReference(
                    ProductRequest::class,
                    $productRequest->id
                )
                ->byChangeType(InventoryAudit::CHANGE_TYPE_PRODUCT_REQUEST_REVERSED)
                ->where('created_at', '>=', now()->subMinutes(5)) // Within last 5 minutes
                ->exists();

                if ($existingReversal) {
                    Log::warning("Duplicate inventory reversal prevented for ProductRequest #{$productRequest->id}");
                    return;
                }

                // Get the spare part and lock it for update
                $sparePart = SparePart::lockForUpdate()->findOrFail($productRequest->spare_part_id);
                
                $previousStock = $sparePart->stock_quantity;
                $quantityToRemove = (int) $productRequest->quantity_requested;
                
                // Validate that we won't go negative
                if ($previousStock < $quantityToRemove) {
                    Log::warning("Cannot reverse ProductRequest #{$productRequest->id}: Would result in negative stock (current: {$previousStock}, requested: {$quantityToRemove})");
                    throw new \Exception("No se puede revertir la solicitud: el stock resultante sería negativo. Stock actual: {$previousStock}, cantidad a restar: {$quantityToRemove}");
                }
                
                // Update stock
                $sparePart->stock_quantity -= $quantityToRemove;
                $sparePart->save();
                
                $newStock = $sparePart->stock_quantity;

                // Create audit trail
                InventoryAudit::createForProductRequestReversed(
                    $productRequest,
                    $previousStock,
                    $newStock,
                    auth()->id()
                );

                Log::info("Inventory decreased for ProductRequest #{$productRequest->id}: Part #{$sparePart->id} stock changed from {$previousStock} to {$newStock}");
            });
        } catch (\Exception $e) {
            Log::error("Failed to decrease inventory for ProductRequest #{$productRequest->id}: {$e->getMessage()}");
            throw $e;
        }
    }
}
