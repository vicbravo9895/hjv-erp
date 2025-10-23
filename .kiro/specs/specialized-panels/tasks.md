# Implementation Plan

- [x] 1. Create database migrations and models
  - Create migration for product_usages table with relationships to spare_parts, maintenance_records, and users
  - Create migration for product_requests table with approval workflow fields
  - Create migration for travel_expenses table with fuel-specific fields and polymorphic attachments
  - Create migration for attachments table with polymorphic relationships
  - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 3.1, 3.2, 3.3, 5.1, 5.2_

- [x] 1.1 Implement ProductUsage model
  - Create ProductUsage model with relationships to SparePart, MaintenanceRecord, User, and Attachment
  - Add validation rules for quantity_used and date_used fields
  - Implement automatic inventory deduction logic in model events
  - _Requirements: 1.2, 1.3_

- [x] 1.2 Implement ProductRequest model
  - Create ProductRequest model with relationships to SparePart and User (requested_by, approved_by)
  - Add status enum and priority enum with proper validation
  - Implement approval workflow methods and scopes
  - _Requirements: 2.1, 2.2, 2.3_

- [x] 1.3 Implement TravelExpense model
  - Create TravelExpense model with relationships to Trip, User, and Attachment
  - Add conditional validation for fuel-related fields based on expense_type
  - Implement automatic trip association logic for current operator
  - _Requirements: 3.1, 3.2, 3.3, 3.5_

- [x] 1.4 Implement Attachment polymorphic model
  - Create Attachment model with morphTo relationship for attachable entities
  - Add file validation methods and storage path generation
  - Implement file cleanup methods for model deletion
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 2. Extend existing middleware and authorization system
  - Create CheckWorkshopAccess middleware following existing pattern (CheckAdminAccess, CheckAccountingAccess)
  - Create CheckOperatorAccess middleware using existing User role methods
  - Register new middleware in bootstrap/app.php following existing pattern
  - Add workshop and operator access methods to existing User model if needed
  - _Requirements: 6.1, 6.3, 7.1, 7.3_

- [x] 2.1 Implement workshop access control
  - Create CheckWorkshopAccess middleware using existing User->hasAdminAccess() or create hasWorkshopAccess() method
  - Follow existing pattern from CheckAdminAccess and CheckAccountingAccess middleware
  - Add workshop.access middleware alias in bootstrap/app.php
  - Implement resource-level policies for workshop-specific data
  - _Requirements: 6.1, 6.2, 6.3_

- [x] 2.2 Implement operator access control
  - Create CheckOperatorAccess middleware using existing User->isOperator() method
  - Follow existing pattern from CheckAdminAccess and CheckAccountingAccess middleware
  - Add operator.access middleware alias in bootstrap/app.php
  - Implement data filtering to show only operator's own trips and expenses using existing Trip relationships
  - _Requirements: 7.1, 7.2, 7.3_

- [x] 3. Create Workshop Panel and Resources
  - Create WorkshopPanelProvider following existing AdminPanelProvider and AccountingPanelProvider pattern
  - Include existing MaintenanceRecordResource and SparePartResource in workshop panel
  - Implement ProductUsageResource with form fields and table columns
  - Implement ProductRequestResource with status workflow and approval features
  - Add workshop-specific navigation groups following existing pattern
  - _Requirements: 6.1, 6.2, 6.4, 6.5, 1.1, 1.2, 2.1, 2.2_

- [x] 3.1 Implement ProductUsageResource
  - Create Filament resource with form fields for spare_part_id, maintenance_record_id, quantity_used
  - Add FileUpload component for attachments with proper validation
  - Implement table with filtering by date, product, and maintenance record
  - Add inventory validation to prevent usage exceeding available stock
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 5.4_

- [x] 3.2 Implement ProductRequestResource
  - Create Filament resource with form fields for product requests and priority selection
  - Add approval workflow actions for authorized users
  - Implement status-based filtering and bulk actions for request management
  - Add notification system for new requests and status changes
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [x] 3.3 Configure workshop dashboard and navigation
  - Create workshop-specific dashboard with relevant statistics widgets
  - Configure navigation menu to show only workshop-relevant resources
  - Add quick access buttons for common workshop tasks
  - Implement workshop statistics: low stock alerts, pending requests, recent usage
  - _Requirements: 6.4, 6.5_

- [x] 4. Create Operator Panel and Resources
  - Create OperatorPanelProvider following existing panel provider pattern
  - Implement TravelExpenseResource with conditional fuel fields and file uploads
  - Create read-only versions of existing TripResource and VehicleResource for operator panel
  - Integrate with existing Trip model and operator relationships
  - Add operator-specific dashboard with trip and expense summaries
  - _Requirements: 7.1, 7.2, 7.4, 7.5, 3.1, 3.2, 3.3, 4.1, 4.2_

- [x] 4.1 Implement TravelExpenseResource
  - Create Filament resource with conditional form fields based on expense_type
  - Add FileUpload component with required validation for expense receipts
  - Implement automatic trip association for current operator's active trips
  - Add expense history table with filtering by date, type, and status
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 5.4_

- [x] 4.2 Configure operator trip and vehicle views
  - Create read-only Trip resource showing only operator's assigned trips
  - Create read-only Vehicle resource showing operator's current vehicle details
  - Add trip status indicators and basic vehicle information display
  - Implement quick expense entry from active trip view
  - _Requirements: 7.2, 7.4_

- [x] 4.3 Implement operator dashboard and expense tracking
  - Create operator dashboard with expense summaries and reimbursement status
  - Add widgets showing pending expenses, total amounts, and recent activity
  - Implement expense status tracking with visual indicators
  - Add quick access to expense entry and trip information
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 7.5_

- [x] 5. Implement custom FileUpload component and attachment system
  - Create enhanced FileUpload component extending Filament's base component
  - Implement file validation for types, sizes, and security checks
  - Add file preview functionality for images and download links for documents
  - Create attachment management service for file operations
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 5.1 Create custom FileUpload component
  - Extend Filament FileUpload with multiple file support and custom validation
  - Add preview functionality for images and proper file type icons
  - Implement secure file naming and storage path generation
  - Add progress indicators and error handling for file uploads
  - _Requirements: 5.1, 5.2, 5.3_

- [x] 5.2 Implement attachment storage service
  - Create service class for handling file storage operations with unique naming
  - Implement file validation methods for type, size, and security checks
  - Add file cleanup methods for orphaned attachments and model deletion
  - Create file retrieval methods with proper access control
  - _Requirements: 5.4, 5.5_

- [x] 6. Enhance existing models with new functionality
  - Extend existing SparePart model with additional inventory tracking methods (ya tiene reduceStock, increaseStock)
  - Extend existing MaintenanceRecord model to support ProductUsage relationships
  - Integrate ProductUsage with existing maintenance_spares pivot table system
  - Enhance existing low stock detection methods in SparePart model
  - _Requirements: 1.3, 1.5, 2.4_

- [x] 6.1 Enhance SparePart model with inventory tracking
  - Add inventory tracking fields and methods to SparePart model
  - Implement stock deduction logic when ProductUsage records are created
  - Add low stock detection methods and alert generation
  - Create inventory history tracking for audit purposes
  - _Requirements: 1.3, 1.5_

- [x] 6.2 Update MaintenanceRecord integration
  - Modify MaintenanceRecord model to support ProductUsage relationships
  - Add methods to calculate total maintenance costs including parts used
  - Implement validation to ensure maintenance records can accept product usage
  - Add summary methods for parts used per maintenance session
  - _Requirements: 1.1, 1.2_

- [x] 7. Configure routing and panel registration
  - Register WorkshopPanelProvider and OperatorPanelProvider in bootstrap/providers.php following existing pattern
  - Configure panel-specific routes and authentication redirects following existing AdminPanel/AccountingPanel pattern
  - Add role-based login redirection to appropriate panels using existing User role methods
  - Implement panel switching functionality for multi-role users (admin users accessing different panels)
  - _Requirements: 6.1, 7.1_

- [x] 7.1 Register panels and configure routing
  - Register WorkshopPanelProvider and OperatorPanelProvider in bootstrap/providers.php (Laravel 11 pattern)
  - Configure panel-specific middleware aliases in bootstrap/app.php following existing admin.access and accounting.access pattern
  - Add authentication redirects based on existing user roles using User model methods
  - Test panel access with existing user roles (operador, administrador, etc.)
  - _Requirements: 6.1, 6.3, 7.1, 7.3_

- [ ]* 7.2 Write integration tests for panel access and functionality
  - Create tests for role-based panel access and middleware functionality
  - Write tests for ProductUsage creation and inventory deduction
  - Test TravelExpense creation with file attachments and trip association
  - Add tests for file upload security and validation
  - _Requirements: All requirements_