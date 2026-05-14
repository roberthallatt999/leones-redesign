<?php

namespace ExpressionEngine\Addons\Mcp\Support;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Trait for suppressing output to prevent MCP server failures
 *
 * This trait provides methods to suppress output and error reporting
 * to prevent any unexpected output from breaking the MCP protocol.
 * Use this trait in Tools, Prompts, and Resources that need output suppression.
 */
trait SuppressesOutput
{
    /**
     * Suppress output to prevent MCP server from failing
     *
     * This method sets up output buffering and error suppression to prevent
     * any unexpected output from breaking the MCP protocol. Call this at the
     * start of your handle()/fetch() method, and call restoreOutput() at the end.
     *
     * @return array{oldErrorReporting: int, oldDisplayErrors: string, oldLogErrors: string, oldTrackErrors: string}
     *                                                                                                               Returns the original settings so they can be restored later
     */
    protected function suppressOutput(): array
    {
        // Clear any existing output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();

        // Save current error reporting settings
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_get('display_errors');
        $oldLogErrors = ini_get('log_errors');
        $oldTrackErrors = ini_get('track_errors');

        // Suppress errors/warnings that might output
        ini_set('display_errors', '0');
        ini_set('log_errors', '0');
        ini_set('html_errors', '0');
        ini_set('track_errors', '0');

        return [
            'oldErrorReporting' => $oldErrorReporting,
            'oldDisplayErrors' => $oldDisplayErrors,
            'oldLogErrors' => $oldLogErrors,
            'oldTrackErrors' => $oldTrackErrors,
        ];
    }

    /**
     * Restore output settings and clean buffers
     *
     * Call this at the end of your handle()/fetch() method (or in a finally block)
     * to restore the original error reporting settings and clean up output buffers.
     *
     * @param  array  $oldSettings  The settings returned from suppressOutput()
     * @return string Any captured output (for debugging purposes)
     */
    protected function restoreOutput(array $oldSettings): string
    {
        // Clean all output buffers and capture any unexpected output
        $output = '';
        while (ob_get_level() > 0) {
            $buffer = ob_get_clean();
            if (! empty($buffer)) {
                $output .= $buffer;
            }
        }

        // Restore error reporting settings
        error_reporting($oldSettings['oldErrorReporting']);
        if (isset($oldSettings['oldDisplayErrors'])) {
            ini_set('display_errors', $oldSettings['oldDisplayErrors']);
            ini_set('log_errors', $oldSettings['oldLogErrors'] ?? '1');
            ini_set('track_errors', $oldSettings['oldTrackErrors'] ?? '0');
        }

        return $output;
    }
}
