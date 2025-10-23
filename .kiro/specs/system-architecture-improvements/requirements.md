# Requirements Document

## Introduction

This specification addresses critical architectural and user experience issues in the truck fleet management system. The primary focus is resolving data duplication between operators and users, implementing proper validation systems, and improving form usability through wizards and better UX patterns.

## Glossary

- **Fleet_Management_System**: The Laravel-based truck fleet management application using Filament
- **Operator_Entity**: Current separate Operator model that duplicates User functionality
- **User_System**: The authentication and role-based user management system
- **Form_Wizard**: Multi-step form interface that breaks complex forms into manageable steps
- **Validation_Service**: Service responsible for business rule validation and data integrity
- **Resource_Panel**: Filament resource interface for managing specific entity types
- **Product_Request**: A request for purchasing spare parts or inventory items
- **Inventory_System**: The spare parts stock management system
- **Admin_User**: User with administrator or super_admin role
- **Accountant_User**: User with contador (accountant) role

## Requirements

### Requirement 1

**User Story:** As a system administrator, I want operators to be users with a specific role instead of separate entities, so that I can maintain data consistency and avoid duplication.

#### Acceptance Criteria

1. WHEN the system processes operator data, THE Fleet_Management_System SHALL use the User model with operator role instead of separate Operator entities
2. THE Fleet_Management_System SHALL migrate existing operator data to the User system while preserving all relationships
3. THE Fleet_Management_System SHALL maintain referential integrity across all operator-related tables during migration
4. WHERE operator-specific fields exist, THE Fleet_Management_System SHALL extend the User model to include these fields
5. THE Fleet_Management_System SHALL update all existing relationships to reference users instead of operators

### Requirement 2

**User Story:** As a user filling out complex forms, I want step-by-step wizards for maintenance records and travel expenses, so that I can complete forms without feeling overwhelmed.

#### Acceptance Criteria

1. WHEN a user creates a maintenance record, THE Fleet_Management_System SHALL present a multi-step wizard interface
2. THE Fleet_Management_System SHALL divide the maintenance form into logical steps: identification, description, parts, and evidence
3. WHEN a user progresses through wizard steps, THE Fleet_Management_System SHALL validate each step before allowing progression
4. THE Fleet_Management_System SHALL display progress indicators showing current step and completion status
5. WHERE form data is complex, THE Fleet_Management_System SHALL provide contextual help and field explanations

### Requirement 3

**User Story:** As a trip coordinator, I want the system to prevent vehicle scheduling conflicts, so that I can avoid double-booking vehicles for overlapping trips.

#### Acceptance Criteria

1. WHEN a user assigns a vehicle to a trip, THE Fleet_Management_System SHALL validate against existing trip schedules
2. IF a scheduling conflict exists, THEN THE Fleet_Management_System SHALL display clear conflict information and prevent assignment
3. THE Fleet_Management_System SHALL check for overlaps in both start and end dates when validating trip assignments
4. WHERE trip modifications occur, THE Fleet_Management_System SHALL revalidate scheduling conflicts
5. THE Fleet_Management_System SHALL provide alternative vehicle suggestions when conflicts are detected

### Requirement 4

**User Story:** As a workshop manager, I want real-time stock validation during maintenance record creation, so that I can prevent using parts that are not available.

#### Acceptance Criteria

1. WHEN a user selects spare parts for maintenance, THE Fleet_Management_System SHALL display current stock levels
2. IF requested quantity exceeds available stock, THEN THE Fleet_Management_System SHALL prevent form submission and show stock shortage warning
3. THE Fleet_Management_System SHALL update stock calculations in real-time as quantities are modified
4. THE Fleet_Management_System SHALL reserve selected parts during form completion to prevent race conditions
5. WHERE stock is insufficient, THE Fleet_Management_System SHALL suggest alternative parts or reduced quantities

### Requirement 5

**User Story:** As a user working with role-restricted forms, I want clear explanations when fields are hidden or disabled, so that I understand why certain options are not available.

#### Acceptance Criteria

1. WHEN form fields are hidden due to role restrictions, THE Fleet_Management_System SHALL display explanatory messages
2. THE Fleet_Management_System SHALL indicate which role level is required to access restricted fields
3. WHERE conditional fields exist, THE Fleet_Management_System SHALL explain the conditions that control field visibility
4. THE Fleet_Management_System SHALL provide consistent messaging patterns across all role-restricted interfaces
5. THE Fleet_Management_System SHALL maintain field visibility rules without compromising security

### Requirement 6

**User Story:** As an inventory manager, I want the system to automatically update stock levels when product requests are received, so that inventory records remain accurate without manual intervention.

#### Acceptance Criteria

1. WHEN a product request is marked as received, THE Fleet_Management_System SHALL automatically increase the spare part stock quantity by the requested amount
2. THE Fleet_Management_System SHALL create an audit trail entry recording the stock change with timestamp and user information
3. IF a product request status changes from received back to another status, THEN THE Fleet_Management_System SHALL reverse the stock adjustment
4. THE Fleet_Management_System SHALL prevent duplicate stock updates for the same product request
5. WHERE stock adjustments occur, THE Fleet_Management_System SHALL validate that the resulting stock quantity is non-negative

### Requirement 7

**User Story:** As a system administrator, I want only administrators and accountants to approve product requests, so that purchasing decisions are controlled by authorized personnel.

#### Acceptance Criteria

1. WHEN a user attempts to approve a product request, THE Fleet_Management_System SHALL verify the user has Admin_User or Accountant_User role
2. THE Fleet_Management_System SHALL hide approval actions from users without administrator or accountant roles
3. IF an unauthorized user attempts to approve a request, THEN THE Fleet_Management_System SHALL deny the action and display an authorization error
4. THE Fleet_Management_System SHALL allow administrators and accountants to view all pending product requests
5. THE Fleet_Management_System SHALL record which administrator or accountant approved each request