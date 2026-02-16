<?php

/*
----------------------------------
 ------  Created: 021626   ------
 ------  nzxl	           ------
----------------------------------
*/

interface TrivyCLI
{
    public const UPDATE_DB      = '/usr/bin/trivy image --download-db-only --no-progress --cache-dir %s';
    public const UPDATE_DB_JAVA = '/usr/bin/trivy image --download-java-db-only --no-progress --cache-dir %s';
    public const SCAN_IMAGE     = '/usr/bin/trivy image --format json --scanners vuln --skip-db-update --timeout 6m --module-dir %s.modules --cache-dir %s --output %s %s';
}
