<?php

namespace BoldMinded\Ansel\Extensions;

use BoldMinded\Ansel\Service\LivePreviewCleaner;
use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;

class AfterChannelEntrySave extends AbstractRoute
{
    public function process(ChannelEntry $channelEntry, array $entryData): void
    {
        (new LivePreviewCleaner())->cleanByChannelEntry($channelEntry);

        // File usages aren't available when in compatibility mode
        if(bool_config_item('file_manager_compatibility_mode')) {
            return;
        }

        // Grab the files used data from SaveRow->updateFileUsage(),
        // which will later be used by CoreBoot.
        $usages = ee()->session->cache('ansel', 'usages');

        if (!$usages || (is_array($usages) && count($usages) === 0)) {
            return;
        }

        ee()->session->set_flashdata('ansel_usages', $usages);
    }
}
