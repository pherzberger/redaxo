<?php

/**
 * Cronjob Addon.
 *
 * @author gharlan[at]web[dot]de Gregor Harlan
 *
 * @package redaxo5
 */

$func = rex_request('func', 'string');
$error = '';
$success = '';
$message = '';
$logFile = rex_path::log('cronjob.log');

// Prepare values for log entry count selection
$ENTRIES_PER_PAGE_MAX_VALUE = 180;
$ENTRIES_PER_PAGE_STEP = 30;
$entriesPerPage = rex_request('log_entries', 'int', $ENTRIES_PER_PAGE_STEP);

if ($entriesPerPage < 1 || $entriesPerPage > $ENTRIES_PER_PAGE_MAX_VALUE) {
    $entriesPerPage = $ENTRIES_PER_PAGE_STEP;
}


if ('cronjob_delLog' == $func) {
    if (rex_log_file::delete($logFile)) {
        $success = rex_i18n::msg('syslog_deleted');
    } else {
        $error = rex_i18n::msg('syslog_delete_error');
    }
}
if ('' != $success) {
    $message .= rex_view::success($success);
}
if ('' != $error) {
    $message .= rex_view::error($error);
}
$content = '';

$content .= '
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="rex-table-icon"></th>
                        <th>' . rex_i18n::msg('cronjob_log_date') . '</th>
                        <th>' . rex_i18n::msg('cronjob_name') . '</th>
                        <th>' . rex_i18n::msg('cronjob_log_message') . '</th>
                        <th>' . rex_i18n::msg('cronjob_environment') . '</th>
                    </tr>
                </thead>
                <tbody>';

$formElements = [];

$file = new rex_log_file($logFile);

/** @var rex_log_entry $entry */
foreach (new LimitIterator($file, 0, $entriesPerPage) as $entry) {
    $data = $entry->getData();
    $class = 'ERROR' == trim($data[0]) ? 'rex-state-error' : 'rex-state-success';
    if ('--' == $data[1]) {
        $icon = '<i class="rex-icon rex-icon-cronjob" title="' . rex_i18n::msg('cronjob_not_editable') . '"></i>';
    } else {
        $icon = '<a href="' . rex_url::backendPage('cronjob', ['list' => 'cronjobs', 'func' => 'edit', 'oid' => $data[1]]) . '" title="' . rex_i18n::msg('cronjob_edit') . '"><i class="rex-icon rex-icon-cronjob"></i></a>';
    }
    $content .= '
        <tr class="' . $class . '">
            <td class="rex-table-icon">' . $icon . '</td>
            <td data-title="' . rex_i18n::msg('cronjob_log_date') . '" class="rex-table-tabular-nums">' . rex_formatter::intlDateTime($entry->getTimestamp(), [IntlDateFormatter::SHORT, IntlDateFormatter::MEDIUM]) . '</td>
            <td data-title="' . rex_i18n::msg('cronjob_name') . '">' . rex_escape($data[2]) . '</td>
            <td data-title="' . rex_i18n::msg('cronjob_log_message') . '">' . nl2br(rex_escape($data[3])) . '</td>
            <td data-title="' . rex_i18n::msg('cronjob_environment') . '">' . (isset($data[4]) ? rex_i18n::msg('cronjob_environment_'.$data[4]) : '') . '</td>
        </tr>';
}

// XXX calc last line and use it instead
if ($url = rex_editor::factory()->getUrl($logFile, 1)) {
    $n = [];
    $n['field'] = '<a class="btn btn-save" href="'. $url .'">' . rex_i18n::msg('system_editor_open_file', rex_path::basename($logFile)) . '</a>';
    $formElements[] = $n;
}

$n = [];
$n['field'] = '<button class="btn btn-delete" type="submit" name="del_btn" data-confirm="' . rex_i18n::msg('cronjob_delete_log_msg') . '?">' . rex_i18n::msg('syslog_delete') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

$content .= '
                </tbody>
            </table>';

$fragment = new rex_fragment();
$fragment->setVar('content', $content, false);
$fragment->setVar('buttons', $buttons, false);
$content = $fragment->parse('core/page/section.php');

$content = '
    <form action="' . rex_url::currentBackendPage() . '" method="post">
        <input type="hidden" name="func" value="cronjob_delLog" />
        ' . $content . '
    </form>';

// Entry count selection
$entryCountSelectionContent = '
    <form action="' . rex_url::currentBackendPage() . '" id="log-paginator-form" method="post">
        <select name="log_entries" onchange="document.getElementById(\'log-paginator-form\').submit()">';

        for ($i = $ENTRIES_PER_PAGE_STEP; $i <= $ENTRIES_PER_PAGE_MAX_VALUE; $i += $ENTRIES_PER_PAGE_STEP) {
            $entryCountSelectionContent .= sprintf(
                '<option value="%1$s"%2$s>%1$s</option>',
                $i,
                $i == $entriesPerPage ? ' selected="selected"' : ''
            );
        }

$entryCountSelectionContent .= '</select>
        <input type="submit" value="' . rex_i18n::msg('update') . '" />
    </form>';


$content = $entryCountSelectionContent . $content;

echo $message;
echo $content;
