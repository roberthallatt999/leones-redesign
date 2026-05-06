<?php

namespace Solspace\Addons\FreeformNext\Repositories;

use Solspace\Addons\FreeformNext\Model\SettingsModel;

class SettingsRepository extends Repository
{
    /** @var SettingsModel[] */
    private static $cache;

    /**
     * @return SettingsRepository
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }

    /**
     * @return SettingsModel
     */
    public function getOrCreate()
    {
        $this->ensureSpamFolderEnabledColumnExists();

        $siteId = ee()->config->item('site_id');

        if (!isset(self::$cache[$siteId])) {
            /** @var SettingsModel $model */
            $model = ee('Model')
                ->get(SettingsModel::MODEL)
                ->filter('siteId', $siteId)
                ->first();

            if (!$model) {
                $model = SettingsModel::create();
            }

            self::$cache[$siteId] = $model;
        }

        return self::$cache[$siteId];
    }

    private function ensureSpamFolderEnabledColumnExists(): void
    {
        $settingsTable = ee()->db->dbprefix('freeform_next_settings');

        if (!ee()->db->table_exists($settingsTable)) {
            return;
        }

        if (ee()->db->field_exists('spamFolderEnabled', $settingsTable)) {
            return;
        }

        try {
            ee()->db->query("ALTER TABLE `{$settingsTable}` ADD COLUMN `spamFolderEnabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 AFTER `spamBlockLikeSuccessfulPost`");
        } catch (\Exception $exception) {
            // swallow race conditions
            if (strpos($exception->getMessage(), 'Duplicate column name') === false) {
                throw $exception;
            }
        }
    }
}
