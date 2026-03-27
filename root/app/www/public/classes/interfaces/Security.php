<?php

/*
----------------------------------
 ------  Created: 021626   ------
 ------  nzxl	           ------
----------------------------------
*/

interface SecurityScanner
{
    //-- TRIVY SCANNER
    public const TRIVY_ID         = 1;
    public const TRIVY_SCAN_IMAGE = '/usr/local/bin/trivy image -q --format json --scanners vuln --timeout 6m --module-dir %s.modules --cache-dir %s --output %s %s';

    //-- GRYPE SCANNER
    public const GRYPE_ID         = 0;
    public const GRYPE_SCAN_IMAGE = 'GRYPE_DB_CACHE_DIR=%s /usr/local/bin/grype --output json --file %s %s';

    //-- SNYK SCANNER
    public const SNYK_ID         = 2;
    public const SNYK_SCAN_IMAGE = 'SNYK_CACHE_PATH=%s/.snyk SNYK_TOKEN=%s snyk -q container test --json --json-file-output=%s %s';
}
