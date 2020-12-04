<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAdminUi\Behat\PageElement;

use EzSystems\Behat\Browser\Component\Component;
use EzSystems\Behat\Browser\Locator\VisibleCSSLocator;

class Dialog extends Component
{
    public function confirm(): void
    {
        $this->getHTMLPage()->find($this->getLocator('confirm'))->click();
    }

    public function decline(): void
    {
        $this->getHTMLPage()->find($this->getLocator('decline'))->click();
    }

    public function verifyIsLoaded(): void
    {
        $this->getHTMLPage()->setTimeout(3)->find($this->getLocator('confirm'))->assert()->isVisible();
        $this->getHTMLPage()->find($this->getLocator('decline'))->assert()->isVisible();
    }

    protected function specifyLocators(): array
    {
        return [
            new VisibleCSSLocator('confirm', '.modal.show button[type="submit"],.modal.show button[data-click]'),
            new VisibleCSSLocator('decline', '.modal.show .btn-secondary'),
        ];
    }
}
