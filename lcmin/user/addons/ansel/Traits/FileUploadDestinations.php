<?php

namespace BoldMinded\Ansel\Traits;

use BoldMinded\Ansel\Dependency\Litzinger\Basee\App;

trait FileUploadDestinations
{
    /**
     * Copied from FileManagerTrait, but to keep backwards compatibility with v6, we can't use the Trait :(
     *
     * @return array
     */
    public function getUploadLocationsAndDirectoriesDropdownChoices()
    {
        $uploadLocationsAndDirectoriesDropdownChoices = [
            '0' => 'None'
        ];

        if (ee('Permission')->can('upload_new_files')) {
            $upload_destinations = ee('Model')->get('UploadDestination')
                ->fields('id', 'name', 'adapter')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('module_id', 0)
                ->order('name', 'asc')
                ->all();

            if (! ee('Permission')->isSuperAdmin()) {
                $member = ee()->session->getMember();
                $upload_destinations = $upload_destinations->filter(function ($dir) use ($member) {
                    return $dir->memberHasAccess($member);
                });
            }

            foreach ($upload_destinations as $upload_pref) {
                $uploadLocationsAndDirectoriesDropdownChoices[$upload_pref->getId() . '.0'] = [
                    'label' => '<i class="fal fa-hdd"></i>' . $upload_pref->name,
                    'upload_location_id' => $upload_pref->id,
                    'adapter' => $upload_pref->adapter,
                    'directory_id' => 0,
                    'path' => '',
                    'children' => !bool_config_item('file_manager_compatibility_mode') ? $upload_pref->buildDirectoriesDropdown($upload_pref->getId(), true) : []
                ];
            }
        }

        return $uploadLocationsAndDirectoriesDropdownChoices;
    }

    public function getAllUploadDestinationIds()
    {
        $choices = $this->getUploadLocationsAndDirectoriesDropdownChoices();

        return $this->fetchIds($choices);
    }

    private function fetchIds(array $choices, array $carry = []): array
    {
        foreach ($choices as $id => $choice) {
            if ($id === 0) {
                continue;
            }

            $carry[] = $id;

            if (is_array($choice['children']) && count($choice['children']) > 0) {
                $carry = $this->fetchIds($choice['children'], $carry);
            }
        }

        return $carry;
    }

    public function buildFileUploadDropdown(string $fieldName, string $defaultValue)
    {
        // Get upload folders
        ee()->db->select("id, name");
        ee()->db->from("upload_prefs");
        ee()->db->order_by("id");
        $query = ee()->db->get();
        $folders = [];

        foreach ($query->result_array() as $row) {
            $folders[$row["id"]] = $row["name"];
        }

        if (str_contains($defaultValue, 'ee:')) {
            $defaultValue = str_replace('ee:', '', $defaultValue);
        }

        if (App::isGteEE7() && !bool_config_item('file_manager_compatibility_mode')) {
            // We have an old pre-EE7 file format
            if (strpos($defaultValue, '.') === false) {
                $defaultValue = $defaultValue . '.0';
            }

            return '<div class="multilevel-select">' . ee('View')->make('ee:_shared/form/fields/dropdown')->render([
                    'field_name' => $fieldName,
                    'choices' => $this->getUploadLocationsAndDirectoriesDropdownChoices(),
                    'value' => $defaultValue,
                    'fileManager' => true,
                ]) . '</div>';
        }

        return form_dropdown(
            $fieldName,
            $folders,
            $defaultValue
        );
    }
}
