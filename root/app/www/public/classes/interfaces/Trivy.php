<?php

/*
----------------------------------
 ------  Created: 021626   ------
 ------  nzxl	           ------
----------------------------------
*/

interface TrivyCLI
{
    public const UPDATE_DB      = '/usr/local/bin/trivy image -q --download-db-only --no-progress --cache-dir %s';
    public const UPDATE_DB_JAVA = '/usr/local/bin/trivy image -q --download-java-db-only --no-progress --cache-dir %s';
    public const SCAN_IMAGE     = '/usr/local/bin/trivy image -q --format json --scanners vuln --skip-db-update --skip-java-db-update --timeout 6m --module-dir %s.modules --cache-dir %s --output %s %s';
}
