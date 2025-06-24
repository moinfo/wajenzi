# BOQ Template Design System

## Overview

The BOQ (Bill of Quantities) Template Design System allows users to create reusable templates for specific building construction types. These templates provide a structured approach to generating BOQs by pre-defining construction stages, activities, sub-activities, and materials that can be selected and customized for different projects.

## System Architecture

### Core Components

The BOQ Template system is built around a hierarchical structure:

```
BOQ Template
├── Construction Stages
    ├── Activities
        ├── Sub-Activities
            └── Materials (BOQ Items)
```

### Database Schema

```sql
-- Building types for categorizing templates
CREATE TABLE building_types (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Categories for organizing BOQ items
CREATE TABLE boq_item_categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    parent_id BIGINT UNSIGNED NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES boq_item_categories(id) ON DELETE SET NULL
);

-- Construction stages/phases
CREATE TABLE construction_stages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Main activities under each stage
CREATE TABLE activities (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    construction_stage_id BIGINT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (construction_stage_id) REFERENCES construction_stages(id) ON DELETE CASCADE
);

-- Sub-activities under each activity with time tracking
CREATE TABLE sub_activities (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    activity_id BIGINT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    estimated_duration_hours DECIMAL(8,2), -- Base duration in hours
    duration_unit ENUM('hours', 'days', 'weeks') DEFAULT 'days',
    labor_requirement INT, -- Number of workers needed
    skill_level ENUM('unskilled', 'semi_skilled', 'skilled', 'specialist') DEFAULT 'semi_skilled',
    can_run_parallel BOOLEAN DEFAULT FALSE, -- Can overlap with other activities
    weather_dependent BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
);

-- Material/item master list
CREATE TABLE boq_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    unit VARCHAR(50),
    base_price DECIMAL(15,2),
    category_id BIGINT UNSIGNED,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES boq_item_categories(id) ON DELETE SET NULL
);

-- Link materials to sub-activities
CREATE TABLE sub_activity_materials (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sub_activity_id BIGINT UNSIGNED,
    boq_item_id BIGINT UNSIGNED,
    quantity DECIMAL(10,2) DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (sub_activity_id) REFERENCES sub_activities(id) ON DELETE CASCADE,
    FOREIGN KEY (boq_item_id) REFERENCES boq_items(id) ON DELETE CASCADE
);

-- BOQ Templates
CREATE TABLE boq_templates (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    building_type_id BIGINT UNSIGNED,
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (building_type_id) REFERENCES building_types(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Template structure - selected stages
CREATE TABLE boq_template_stages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    boq_template_id BIGINT UNSIGNED,
    construction_stage_id BIGINT UNSIGNED,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (boq_template_id) REFERENCES boq_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (construction_stage_id) REFERENCES construction_stages(id)
);

-- Template structure - selected activities
CREATE TABLE boq_template_activities (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    boq_template_stage_id BIGINT UNSIGNED,
    activity_id BIGINT UNSIGNED,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (boq_template_stage_id) REFERENCES boq_template_stages(id) ON DELETE CASCADE,
    FOREIGN KEY (activity_id) REFERENCES activities(id)
);

-- Template structure - selected sub-activities with materials
CREATE TABLE boq_template_sub_activities (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    boq_template_activity_id BIGINT UNSIGNED,
    sub_activity_id BIGINT UNSIGNED,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (boq_template_activity_id) REFERENCES boq_template_activities(id) ON DELETE CASCADE,
    FOREIGN KEY (sub_activity_id) REFERENCES sub_activities(id)
);
```

#### Master Data Tables

**1. Building Types**
- `building_types`: Categorizes templates by building type
  - Fields: `name`, `description`, `is_active`
  - Examples: Residential House, Commercial Building, Industrial Facility, etc.

**2. BOQ Item Categories**
- `boq_item_categories`: Hierarchical categorization of materials and items
  - Fields: `name`, `description`, `parent_id`, `sort_order`, `is_active`
  - Examples: Structural Materials > Concrete > Ready Mix, Finishing > Paint > Interior Paint
  - Supports nested categories for better organization

**3. Construction Stages**
- `construction_stages`: Defines major construction phases
  - Fields: `name`, `description`, `sort_order`
  - Examples: Foundation, Structural Work, Finishing, etc.

**4. Activities**
- `activities`: Main work categories within each stage
  - Fields: `name`, `description`, `construction_stage_id`, `sort_order`
  - Examples: Excavation, Concrete Works, Plastering, etc.

**5. Sub-Activities**
- `sub_activities`: Detailed tasks within each activity with time tracking
  - Fields: `name`, `description`, `activity_id`, `estimated_duration_hours`, `duration_unit`, `labor_requirement`, `skill_level`, `can_run_parallel`, `weather_dependent`, `sort_order`
  - Examples: Site Preparation (2 days, 4 workers), Footing Excavation (1 day, 3 skilled workers), Wall Plastering (3 days, 2 semi-skilled workers)
  - Enables project scheduling, resource planning, and progress tracking

**6. BOQ Items (Materials)**
- `boq_items`: Master list of materials and items
  - Fields: `name`, `description`, `unit`, `base_price`, `category_id`
  - Examples: Cement bags, Steel bars, Paint cans, etc.
  - Now linked to categories for better organization

**7. Material Associations**
- `sub_activity_materials`: Links materials to sub-activities
  - Fields: `sub_activity_id`, `boq_item_id`, `quantity`

#### Template Structure Tables

**8. BOQ Templates**
- `boq_templates`: Main template definitions
  - Fields: `name`, `description`, `building_type_id`, `is_active`, `created_by`
  - Now linked to building_types table for better data integrity

**9. Template Stages**
- `boq_template_stages`: Selected stages for each template
  - Fields: `boq_template_id`, `construction_stage_id`, `sort_order`

**10. Template Activities**
- `boq_template_activities`: Selected activities for each template stage
  - Fields: `boq_template_stage_id`, `activity_id`, `sort_order`

**11. Template Sub-Activities**
- `boq_template_sub_activities`: Selected sub-activities with their materials
  - Fields: `boq_template_activity_id`, `sub_activity_id`, `sort_order`

## How It Works

### 1. Master Data Setup
First, administrators set up the master data:
- Define building types (Residential, Commercial, Industrial, etc.)
- Create hierarchical categories for BOQ items (Materials > Concrete > Ready Mix)
- Define construction stages (Foundation, Structure, Finishing, etc.)
- Create activities under each stage (Excavation, Concrete, Plumbing, etc.)
- Add sub-activities with detailed descriptions and time estimates:
  - Duration in hours/days/weeks
  - Labor requirements (number of workers)
  - Skill level requirements
  - Parallel execution capability
  - Weather dependency flags
- Maintain a comprehensive list of BOQ items/materials with proper categorization
- Associate materials with relevant sub-activities

### 2. Template Creation Process
Users can create templates by:
1. **Template Info**: Name, description, and select building type from predefined list
2. **Stage Selection**: Choose relevant construction stages
3. **Activity Selection**: Select activities within each chosen stage
4. **Sub-Activity Selection**: Pick specific sub-activities with their materials
5. **Material Organization**: Browse materials by categories for easier selection
6. **Customization**: Adjust quantities and add custom items if needed

### 3. Template Usage
When creating a project BOQ:
1. Select an appropriate template based on building type
2. Template automatically populates the BOQ structure with:
   - All materials and quantities
   - Estimated durations for each sub-activity
   - Labor requirements and skill levels
   - Activity dependencies and scheduling
3. User can modify quantities, prices, durations, and add/remove items
4. Generate final BOQ with integrated project timeline
5. Export project schedule (Gantt chart) and resource allocation plan

## Benefits

### For Administrators
- **Standardization**: Consistent BOQ structures across projects
- **Efficiency**: Reduce time spent on BOQ creation
- **Quality Control**: Ensure all necessary items are included
- **Cost Estimation**: Better accuracy through standardized templates
- **Project Scheduling**: Auto-generate timelines from template durations
- **Resource Planning**: Estimate workforce and skill requirements

### For Users
- **Speed**: Quick BOQ generation using pre-built templates
- **Completeness**: Reduced risk of missing items
- **Flexibility**: Can customize templates for specific project needs
- **Learning**: New users can learn from standardized structures
- **Time Management**: Built-in duration estimates for better planning
- **Progress Tracking**: Compare actual vs estimated completion times

## Integration with Existing System

The template system integrates seamlessly with the existing BOQ system:

### Existing Tables
- `project_boqs`: Stores generated BOQs from templates
- `project_boq_items`: Individual line items in project BOQs

### Workflow Integration
1. User selects a template when creating a new project BOQ
2. Template data populates the project BOQ structure
3. User customizes as needed
4. Final BOQ is saved to existing `project_boqs` and `project_boq_items` tables

## API Endpoints (Recommended)

### Template Management
```
GET    /api/boq-templates           # List all templates
POST   /api/boq-templates           # Create new template
GET    /api/boq-templates/{id}      # Get template details
PUT    /api/boq-templates/{id}      # Update template
DELETE /api/boq-templates/{id}      # Delete template
```

### Master Data Management
```
GET    /api/building-types          # List all building types
GET    /api/boq-item-categories     # List BOQ item categories (hierarchical)
GET    /api/construction-stages     # List all stages
GET    /api/activities              # List activities (optionally by stage)
GET    /api/sub-activities          # List sub-activities (optionally by activity)
GET    /api/boq-items               # List all BOQ items/materials (optionally by category)
```

### Template Usage
```
POST   /api/projects/{id}/boq/from-template/{templateId}  # Generate BOQ from template
```

## User Interface Considerations

### Template Builder
- **Step-by-step wizard**: Guide users through template creation
- **Hierarchical tree view**: Show stage > activity > sub-activity relationships
- **Drag-and-drop**: Easy reordering of items
- **Search and filter**: Quick finding of activities and materials
- **Preview**: Show template structure before saving

### Template Selection
- **Template gallery**: Visual cards showing template info
- **Filter by building type**: Quick category filtering
- **Template preview**: Show what's included before selection
- **Comparison view**: Compare multiple templates side-by-side

## Implementation Priority

### Phase 1: Core Structure
1. Create master data tables (stages, activities, sub-activities, items)
2. Build basic CRUD operations for master data
3. Create template tables and relationships

### Phase 2: Template Builder
1. Template creation interface
2. Master data management interface
3. Template selection and usage

### Phase 3: Advanced Features
1. Template versioning
2. Import/export templates
3. Template sharing between users
4. Analytics and usage tracking
5. Project scheduling integration (Gantt charts)
6. Resource allocation and optimization
7. Time tracking and progress monitoring

## Best Practices

### Data Management
- **Regular Updates**: Keep master data current with market changes
- **Validation**: Ensure data integrity through proper constraints
- **Backup**: Regular backups of template and master data
- **Version Control**: Track changes to templates over time

### User Experience
- **Progressive Disclosure**: Show relevant options based on previous selections
- **Default Values**: Provide sensible defaults to speed up template creation
- **Help Documentation**: Include tooltips and help text
- **Bulk Operations**: Allow bulk selection and modification of items

### Performance
- **Caching**: Cache frequently used templates and master data
- **Pagination**: Handle large datasets efficiently
- **Indexing**: Proper database indexing for fast queries
- **Lazy Loading**: Load template details only when needed

## Security Considerations

- **Access Control**: Role-based permissions for template creation/modification
- **Data Validation**: Sanitize all user inputs
- **Audit Trail**: Track who created/modified templates
- **Backup Security**: Secure storage of template backups

## Conclusion

The BOQ Template Design System provides a robust foundation for standardizing and streamlining BOQ creation in construction projects. By providing a hierarchical structure of stages, activities, sub-activities, and materials, users can quickly generate comprehensive and accurate BOQs while maintaining flexibility for project-specific customizations.

The system's modular design allows for easy maintenance and updates, while the integration with existing BOQ functionality ensures a seamless user experience.