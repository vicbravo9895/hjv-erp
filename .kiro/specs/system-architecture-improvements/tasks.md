# Implementation Plan

- [x] 1. Extend User model with operator fields
  - Create database migration to add operator fields to users table (license_number, phone, hire_date, status)
  - Extend User model with operator-specific methods and relationships
  - Update existing models to reference User instead of Operator
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 1.1 Create database migration for User model extension
  - Write migration file to add operator fields to users table
  - Include proper indexes and constraints for new fields
  - _Requirements: 1.1, 1.4_

- [x] 1.2 Extend User model with operator functionality
  - Add operator-specific fillable fields and relationships (trips, weeklyPayrolls)
  - Implement operator helper methods (getOperatorDisplayName, isOperator, etc.)
  - Add scopes for filtering operators (scopeActiveOperators, etc.)
  - _Requirements: 1.1, 1.4_

- [x] 1.3 Update related models to use User instead of Operator
  - Update Trip model to reference User (operator_id foreign key)
  - Update WeeklyPayroll model to reference User
  - Update TravelExpense model if needed
  - _Requirements: 1.2, 1.3, 1.5_

- [ ]* 1.4 Write unit tests for User model extensions
  - Test operator role detection and helper methods
  - Test relationship integrity after model changes
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 2. Implement centralized validation services
  - Create TripValidationService for vehicle scheduling conflict detection
  - Create StockValidationService for real-time inventory validation
  - Implement ValidationResult and ReservationResult data structures
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 2.1 Create TripValidationService
  - Implement validateVehicleAvailability method with date overlap checking
  - Add validateOperatorAvailability method for operator scheduling conflicts
  - Create helper methods for conflict detection and alternative suggestions
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 2.2 Create StockValidationService
  - Implement validatePartAvailability with real-time stock checking
  - Add reserveParts and releaseParts methods for inventory management
  - Create stock shortage detection and alternative part suggestions
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 2.3 Implement validation result data structures
  - Create ValidationResult class with success/error/warning/suggestion properties
  - Create ReservationResult class for inventory reservation operations
  - Add helper methods for result formatting and user-friendly messages
  - _Requirements: 3.1, 3.2, 4.1, 4.2_

- [ ]* 2.4 Write unit tests for validation services
  - Test trip overlap detection with various date scenarios
  - Test stock validation with different inventory levels
  - Test validation result formatting and error messages
  - _Requirements: 3.1, 3.2, 3.3, 4.1, 4.2, 4.3_

- [x] 3. Create MaintenanceRecord wizard interface
  - Convert MaintenanceRecordResource form to multi-step wizard
  - Implement four logical steps: identification, description, parts, evidence
  - Add step-by-step validation and progress indicators
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [x] 3.1 Create MaintenanceWizard base structure
  - Create CreateMaintenanceRecord page with wizard functionality
  - Define four wizard steps with proper icons and descriptions
  - Implement step navigation and progress tracking
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [x] 3.2 Implement Step 1: Vehicle Identification
  - Create vehicle type selection with live updates
  - Add vehicle selection with search and display information
  - Implement maintenance type selection with descriptive options
  - Add date picker with validation
  - _Requirements: 2.1, 2.2, 2.4_

- [x] 3.3 Implement Step 2: Work Description
  - Create markdown editor for detailed work description
  - Add mechanic assignment with role-based filtering
  - Implement work category selection
  - _Requirements: 2.1, 2.2, 2.4_

- [x] 3.4 Implement Step 3: Parts and Inventory Management
  - Create dynamic parts repeater with real-time stock validation
  - Integrate StockValidationService for availability checking
  - Add cost calculation and total display
  - Implement stock shortage warnings and suggestions
  - _Requirements: 2.1, 2.2, 2.4, 4.1, 4.2, 4.3, 4.4_

- [x] 3.5 Implement Step 4: Evidence and Documentation
  - Create file upload interface for photos and documents
  - Add file type validation and size limits
  - Implement preview and management of uploaded files
  - _Requirements: 2.1, 2.2, 2.4_

- [x] 3.6 Add wizard confirmation and submission
  - Create final review step with summary of all entered data
  - Implement confirmation modal with cost breakdown
  - Add form submission with proper error handling
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [ ]* 3.7 Write integration tests for maintenance wizard
  - Test complete wizard flow from start to finish
  - Test step validation and error handling
  - Test data persistence between wizard steps
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [x] 4. Enhance FormFieldResolver with contextual help
  - Extend FormFieldResolver to provide field visibility explanations
  - Add role-based help text and restriction reasons
  - Implement user-friendly messaging for hidden/disabled fields
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 4.1 Extend FormFieldResolver interface
  - Add getFieldWithHelp method to provide comprehensive field information
  - Implement getFieldHelpText and getRestrictionReason methods
  - Create consistent messaging patterns for role restrictions
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 4.2 Create contextual help components
  - Implement Placeholder components for field restriction explanations
  - Add role-level indicators for restricted fields
  - Create consistent styling for help messages
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 4.3 Update existing resources with enhanced field resolution
  - Apply enhanced FormFieldResolver to MaintenanceRecordResource
  - Update other complex resources (TravelExpenseResource, VehicleResource)
  - Add contextual help throughout the application
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ]* 4.4 Write tests for enhanced field resolver
  - Test role-based field visibility and help text generation
  - Test restriction reason messaging
  - Test consistent behavior across different resources
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 5. Integrate validation services into Trip and Vehicle resources
  - Add TripValidationService to TripResource for scheduling conflict prevention
  - Implement real-time validation in trip creation and editing forms
  - Add user-friendly error messages and alternative suggestions
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 5.1 Integrate TripValidationService into TripResource
  - Add vehicle availability validation to trip creation form
  - Implement real-time conflict checking with live form updates
  - Add operator availability validation for trip assignments
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 5.2 Create user-friendly validation messages
  - Implement ErrorMessageService for consistent error formatting
  - Add visual indicators for scheduling conflicts
  - Create suggestion system for alternative vehicles/dates
  - _Requirements: 3.1, 3.2, 3.5_

- [x] 5.3 Add validation to vehicle assignment workflows
  - Integrate validation into existing vehicle assignment processes
  - Update VehicleAssignmentService to use new validation layer
  - Add conflict resolution workflows
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ]* 5.4 Write feature tests for trip validation integration
  - Test end-to-end trip creation with conflict detection
  - Test alternative suggestion workflows
  - Test multi-user scheduling scenarios
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 6. Clean up deprecated Operator model and update resources
  - Remove deprecated Operator model and related files
  - Update Filament resources that reference Operator
  - Update any forms or selects that use Operator model
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 6.1 Remove deprecated Operator model files
  - Delete Operator model file (app/Models/Operator.php)
  - Remove OperatorResource if it exists
  - Clean up unused migration files and factories for Operator
  - _Requirements: 1.1, 1.5_

- [x] 6.2 Update Filament resources to use User model
  - Update TripResource to use User::operators() scope for operator selection
  - Update WeeklyPayrollResource to use User model
  - Update any other resources that reference Operator
  - _Requirements: 1.2, 1.3, 1.5_

- [x] 6.3 Update form selects and relationships
  - Replace Operator::class references with User::class in forms
  - Update relationship queries to use User::operators() scope
  - Ensure display names use getOperatorDisplayName() method
  - _Requirements: 1.2, 1.3, 1.5_

- [ ]* 6.4 Write integration tests for updated resources
  - Test trip creation with user-based operators
  - Test operator selection in forms
  - Test relationship queries and scopes
  - _Requirements: 1.2, 1.3, 1.5_

- [x] 7. Implement automatic inventory updates for product requests
  - Create InventoryAudit model for tracking stock changes
  - Create ProductRequestObserver to handle automatic stock updates
  - Add duplicate update prevention logic
  - Implement stock reversal when status changes from received
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 7.1 Create InventoryAudit model and migration
  - Create migration for inventory_audits table with all required fields
  - Create InventoryAudit model with relationships to SparePart and User
  - Add helper methods for creating audit entries
  - _Requirements: 6.2_

- [x] 7.2 Create ProductRequestObserver
  - Create observer class to listen for ProductRequest model events
  - Implement updated() method to detect status changes
  - Add logic to increase stock when status becomes 'received'
  - Add logic to decrease stock when status changes from 'received'
  - _Requirements: 6.1, 6.3, 6.4_

- [x] 7.3 Implement stock update logic with audit trail
  - Create method to safely update spare part stock quantity
  - Create InventoryAudit entry for each stock change
  - Add validation to prevent negative stock quantities
  - Implement duplicate update prevention using flags or timestamps
  - _Requirements: 6.1, 6.2, 6.4, 6.5_

- [x] 7.4 Register observer in AppServiceProvider
  - Add ProductRequestObserver registration in boot method
  - Test observer triggers on status changes
  - _Requirements: 6.1_

- [ ]* 7.5 Write tests for inventory automation
  - Test stock increase when product request is marked as received
  - Test stock decrease when status changes from received
  - Test duplicate update prevention
  - Test audit trail creation
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [ ] 8. Enforce approval permissions for product requests
  - Update ProductRequestPermissionService to restrict approvals
  - Update ProductRequestResource to hide unauthorized actions
  - Add authorization checks for all approval-related operations
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 8.1 Update ProductRequestPermissionService
  - Modify canApprove() to only allow admins and accountants
  - Modify canMarkAsOrdered() to only allow admins and accountants
  - Modify canMarkAsReceived() to only allow admins and accountants
  - Add clear authorization error messages
  - _Requirements: 7.1, 7.2, 7.3, 7.5_

- [ ] 8.2 Update ProductRequestResource UI
  - Update table actions visibility based on user role
  - Hide approval buttons from non-admin/non-accountant users
  - Update bulk actions to check permissions
  - Add explanatory messages for restricted actions
  - _Requirements: 7.2, 7.3, 7.4_

- [ ] 8.3 Add server-side authorization checks
  - Add authorization gates for product request approvals
  - Implement middleware or policy checks on approval actions
  - Return proper HTTP 403 responses for unauthorized attempts
  - _Requirements: 7.1, 7.3_

- [ ]* 8.4 Write tests for permission enforcement
  - Test that only admins and accountants can approve requests
  - Test that unauthorized users receive proper error messages
  - Test UI visibility based on user roles
  - _Requirements: 7.1, 7.2, 7.3, 7.4_