<?php

/**
 * This file registers entities via hook_civicrm_managed.
 * Lifecycle events in this extension will cause these registry records to be
 * automatically inserted, updated, or deleted from the database as appropriate.
 * For more details, see "hook_civicrm_managed" (at
 * https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_managed/) as well
 * as "API and the Art of Installation" (at
 * https://civicrm.org/blogs/totten/api-and-art-installation).
 */

return array (
  array (
    'name' => 'CRM_Cpvolhours_OptionGroup_HoursPerRole',
    'entity' => 'OptionGroup',
    'params' =>
    array (
      "name" => "cpvolhours_hours_per_role",
      "title" => "CarePartners Volunteer Hours Per Role",
      "is_reserved" => "1",
      "is_active" => "1",
      "is_locked" => "0"
    ),
  ),
);
