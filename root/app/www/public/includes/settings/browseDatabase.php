<?php

/*
----------------------------------
 ------  Created: 102626   ------
 ------  Austin Best	   ------
----------------------------------
*/

$browseView = strval($browseView ?? 'list');

switch ($browseView) {
    case 'table':
        $browse  = $browse ?? [];
        $sql     = strval($browse['sql'] ?? '');
        $columns = $browse['columns'] ?? [];
        $rows    = $browse['rows'] ?? [];
        $page    = max(1, intval($browse['page'] ?? 1));
        $pages   = max(1, intval($browse['pages'] ?? 1));
        $total   = max(0, intval($browse['total'] ?? 0));
        $perPage = max(1, intval($browse['perPage'] ?? 25));
        $start   = $total ? (($page - 1) * $perPage) + 1 : 0;
        $end     = $total ? min($page * $perPage, $total) : 0;
        ?>
        <div class="browse-database-viewer">
            <div class="browse-database-toolbar">
                <div class="browse-database-query">
                    <code class="small"><?= htmlspecialchars($sql, ENT_QUOTES, 'UTF-8') ?></code>
                </div>
                <div class="browse-database-pager">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="browseDatabasePrev();" <?= $page <= 1 ? ' disabled' : '' ?>>Previous</button>
                    <span class="text-body-secondary small"><?= htmlspecialchars($start . '-' . $end . ' of ' . number_format($total) . ' (page ' . $page . ' of ' . $pages . ')', ENT_QUOTES, 'UTF-8') ?></span>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="browseDatabaseNext();" <?= $page >= $pages ? ' disabled' : '' ?>>Next</button>
                </div>
            </div>
            <div class="browse-database-table table-responsive">
                <?php if ($rows) { ?>
                    <table class="table table-bordered table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <?php foreach ($columns as $field) { ?>
                                    <th><?= htmlspecialchars(strval($field), ENT_QUOTES, 'UTF-8') ?></th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row) { ?>
                                <tr>
                                    <?php foreach ($columns as $field) { ?>
                                        <td><?= htmlspecialchars(strval($row[$field] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <div class="text-body-secondary">This table is empty.</div>
                <?php } ?>
            </div>
        </div>
        <?php
        break;

    case 'schema':
        $browse = $browse ?? [];
        $schema = strval($browse['schema'] ?? '');
        ?>
        <pre class="mb-0 small overflow-auto"><?= htmlspecialchars($schema, ENT_QUOTES, 'UTF-8') ?></pre>
        <?php
        break;

    case 'query':
        $browse   = $browse ?? [];
        $sql      = strval($browse['sql'] ?? '');
        $columns  = $browse['columns'] ?? [];
        $rows     = $browse['rows'] ?? [];
        $total    = max(0, intval($browse['total'] ?? 0));
        $capped   = !empty($browse['capped']);
        $maxRows  = max(1, intval($browse['maxRows'] ?? 500));
        $affected = array_key_exists('affected', $browse) ? $browse['affected'] : null;
        ?>
        <div class="mb-2 d-flex align-items-start">
            <i class="fas fa-pen-to-square me-2" style="cursor: pointer;" title="Edit" onclick="$('#browseDatabaseQuery').val($(this).next().text());"></i>
            <div class="browse-database-query">
                <code class="small"><?= htmlspecialchars($sql, ENT_QUOTES, 'UTF-8') ?></code>
            </div>
        </div>
        <?php if ($capped) { ?>
            <div class="text-body-secondary small mb-2">Showing the first <?= number_format($maxRows) ?> rows.</div>
        <?php } ?>
        <?php if ($rows) { ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $field) { ?>
                                <th><?= htmlspecialchars(strval($field), ENT_QUOTES, 'UTF-8') ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row) { ?>
                            <tr>
                                <?php foreach ($columns as $field) { ?>
                                    <td><?= htmlspecialchars(strval($row[$field] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="text-body-secondary small mt-2"><?= htmlspecialchars(number_format($total) . ' rows', ENT_QUOTES, 'UTF-8') ?></div>
        <?php } else if ($affected != null) { ?>
            <div class="text-body-secondary"><?= htmlspecialchars(number_format(intval($affected)) . ' rows affected', ENT_QUOTES, 'UTF-8') ?></div>
        <?php } else { ?>
            <div class="text-body-secondary">Query returned no rows.</div>
        <?php } ?>
        <?php
        break;

    case 'list':
    default:
        $browseSummary = $browseSummary ?? [];
        ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Columns</th>
                        <th>Rows</th>
                        <th>Size</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$browseSummary) { ?>
                        <tr>
                            <td colspan="5">No tables found.</td>
                        </tr>
                    <?php } ?>
                    <?php foreach ($browseSummary as $browseTable) {
                        $tableName = htmlspecialchars(strval($browseTable['table'] ?? ''), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td><?= $tableName ?></td>
                            <td><?= htmlspecialchars(strval($browseTable['columns'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(number_format(intval($browseTable['rows'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(byteConversion($browseTable['size'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <i class="fas fa-table me-2" style="cursor: pointer;" title="Browse" onclick="viewDatabaseBrowse('<?= $tableName ?>');"></i>
                                <i class="fas fa-list" style="cursor: pointer;" title="Schema" onclick="viewDatabaseSchema('<?= $tableName ?>');"></i>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php
        break;
}
