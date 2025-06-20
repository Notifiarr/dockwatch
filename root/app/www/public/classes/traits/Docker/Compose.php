<?php

/*
----------------------------------
 ------  Created: 062025   ------
 ------  nzxl	             ------
----------------------------------
*/

trait Compose
{
    public function runComposeCommand($params, $container)
    {
        $cmd = sprintf(DockerSock::COMPOSE, $this->shell->prepare($params), $this->shell->prepare($container));
        return $this->shell->exec($cmd . ' 2>&1');
    }
}
