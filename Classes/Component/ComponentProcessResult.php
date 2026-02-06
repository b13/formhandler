<?php

namespace Typoheads\Formhandler\Component;

/*                                                                       *
* This script is part of the TYPO3 project - inspiring people to share!  *
*                                                                        *
* TYPO3 is free software; you can redistribute it and/or modify it under *
* the terms of the GNU General Public License version 2 as published by  *
* the Free Software Foundation.                                          *
*                                                                        *
* This script is distributed in the hope that it will be useful, but     *
* WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
* TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
* Public License for more details.                                       *
*                                                                        */

use Psr\Http\Message\ResponseInterface;

class ComponentProcessResult
{
    public function __construct(
        public readonly ?ResponseInterface $response = null,
        public readonly ?array $gp = null
    ) {}

    public function hasGp(): bool
    {
        return $this->gp !== null;
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }

}
