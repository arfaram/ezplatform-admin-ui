<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAdminUi\Behat\PageObject;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\URLAlias;
use EzSystems\Behat\Browser\Page\Browser;
use EzSystems\Behat\Browser\Page\Page;
use EzSystems\Behat\Browser\Locator\VisibleCSSLocator;
use EzSystems\EzPlatformAdminUi\Behat\PageElement\Breadcrumb;
use EzSystems\EzPlatformAdminUi\Behat\PageElement\ContentItemAdminPreview;
use EzSystems\EzPlatformAdminUi\Behat\PageElement\ContentTypePicker;
use EzSystems\EzPlatformAdminUi\Behat\PageElement\Dialog;
use EzSystems\EzPlatformAdminUi\Behat\PageElement\LanguagePicker;
use EzSystems\EzPlatformAdminUi\Behat\PageElement\RightMenu;
use EzSystems\EzPlatformAdminUi\Behat\PageElement\SubItemsList;
use PHPUnit\Framework\Assert;

class ContentViewPage extends Page
{
    /** @var \EzSystems\EzPlatformAdminUi\Behat\PageElement\RightMenu Element representing the right menu */
    private $rightMenu;

    /** @var \EzSystems\EzPlatformAdminUi\Behat\PageElement\SubItemsList */
    private $subItemList;

    /** @var string */
    private $locationPath;

    /** @var \EzSystems\EzPlatformAdminUi\Behat\PageElement\ContentTypePicker */
    private $contentTypePicker;

    /** @var ContentUpdateItemPage */
    private $contentUpdatePage;

    /** @var string */
    private $expectedContentType;

    /** @var \EzSystems\EzPlatformAdminUi\Behat\PageElement\LanguagePicker */
    private $languagePicker;
    /**
    /** @var string */
    private $expectedContentName;

    /** @var \EzSystems\EzPlatformAdminUi\Behat\PageElement\Dialog */
    private $dialog;

    private $route;

    /** @var \EzSystems\EzPlatformAdminUi\Behat\PageElement\Breadcrumb */
    private $breadcrumb;

    /** @var \EzSystems\EzPlatformAdminUi\Behat\PageElement\ContentItemAdminPreview */
    private $contentItemAdminPreview;
    /**
     * @var UserUpdatePage
     */
    private $userUpdatePage;
    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var mixed
     */
    private $expectedIsContainer;

    public function __construct(
        Browser $browser,
        RightMenu $rightMenu,
        SubItemsList $subItemList,
        ContentTypePicker $contentTypePicker,
        ContentUpdateItemPage $contentUpdatePage,
        LanguagePicker $languagePicker,
        Dialog $dialog,
        Repository $repository,
        Breadcrumb $breadcrumb,
        ContentItemAdminPreview $contentItemAdminPreview,
        UserUpdatePage $userUpdatePage
    ) {
        parent::__construct($browser);

        $this->rightMenu = $rightMenu;
        $this->subItemList = $subItemList;
        $this->subItemList->shouldHaveGridViewEnabled($this->hasGridViewEnabledByDefault());
        $this->contentTypePicker = $contentTypePicker;
        $this->contentUpdatePage = $contentUpdatePage;
        $this->languagePicker = $languagePicker;
        $this->dialog = $dialog;
        $this->breadcrumb = $breadcrumb;
        $this->contentItemAdminPreview = $contentItemAdminPreview;
        $this->userUpdatePage = $userUpdatePage;
        $this->repository = $repository;
    }

    public function startCreatingContent(string $contentTypeName): ContentUpdateItemPage
    {
        $this->rightMenu->clickButton('Create');
        $this->contentTypePicker->verifyIsLoaded();
        $this->contentTypePicker->select($contentTypeName);
        $this->contentUpdatePage->verifyIsLoaded();

        return $this->contentUpdatePage;
    }

    public function startCreatingUser(): UserUpdatePage
    {
        $this->rightMenu->clickButton('Create');
        $this->contentTypePicker->verifyIsLoaded();
        $this->contentTypePicker->select('User');
        $this->userUpdatePage->verifyIsLoaded();

        return $this->userUpdatePage;
    }

    public function goToSubItem(string $contentName, string $contentType): void
    {
        throw new \Exception('jak najmniej tego uzywac...');
        $this->subItemList->sortBy('Modified', false);

        $this->subItemList->clickListElement($contentName, $contentType);

        $this->setExpectedLocationPath(sprintf('%s/%s', $this->locationPath, $contentName));
        $this->verifyIsLoaded();
    }

    public function navigateToPath(string $path): void
    {
        throw new \Exception('jak najmniej tego uzywac...');
        // $pathArray = explode('/', $path);
        // $menuTab = $pathArray[0] === EnvironmentConstants::get('ROOT_CONTENT_NAME') ? 'Content structure' : $pathArray[0];

        // $upperMenu = ElementFactory::createElement($this->context, UpperMenu::ELEMENT_NAME);
        // $upperMenu->goToTab('Content');
        // $upperMenu->goToSubTab($menuTab);

        // $pathSize = count($pathArray);
        // if ($pathSize > 1) {
        //     for ($i = 1; $i < $pathSize; ++$i) {
        //         $contentPage = PageObjectFactory::createPage($this->context, self::PAGE_NAME, $pathArray[$i - 1]);
        //         $contentPage->verifyIsLoaded();
        //         $contentPage->subItemList->table->clickListElement($pathArray[$i]);
        //     }
        // }
    }

    private function hasGridViewEnabledByDefault(): bool
    {
        return $this->expectedContentName === 'Media';
    }

    public function setExpectedLocationPath(string $locationPath)
    {
        [$this->expectedContentType, $this->expectedContentName, $contentId, $contentMainLocationId, $isContainer] = $this->getContentData($locationPath);
        $this->route = sprintf('/view/content/%s/full/1/%s', $contentId, $contentMainLocationId);
        $this->expectedIsContainer = $isContainer;
    }

    private function getContentData(string $locationPath): array
    {
        return $this->repository->sudo(function (Repository $repository) use ($locationPath) {
            $content = $this->loadContent($repository, $locationPath);

            return [
                $content->getContentType()->getName(),
                $content->getName(),
                $content->id,
                $content->contentInfo->getMainLocation()->id,
                $content->getContentType()->isContainer,
            ];
        });
    }

    private function loadContent(Repository $repository, string $locationPath): Content
    {
        try {
            $urlAlias = $repository->getURLAliasService()->lookup($locationPath);
            Assert::assertEquals(URLAlias::LOCATION, $urlAlias->type);

            return $repository->getLocationService()
                ->loadLocation($urlAlias->destination)
                ->getContent();
        } catch (NotFoundException $exception) {
            // Sometimes we try to load the Content right after clicking the action button and the app is too slow to create the data...
            sleep(3);
            $urlAlias = $repository->getURLAliasService()->lookup($locationPath);
            Assert::assertEquals(URLAlias::LOCATION, $urlAlias->type);

            return $repository->getLocationService()
                ->loadLocation($urlAlias->destination)
                ->getContent();
        }
    }

    public function verifyIsLoaded(): void
    {
        $this->getHTMLPage()->find($this->getLocator('mainContainer'))->assert()->isVisible();
        $this->rightMenu->verifyIsLoaded();
        Assert::assertContains(
            $this->expectedContentName,
            $this->breadcrumb->getBreadcrumb(),
            'Breadcrumb shows invalid path'
        );

        if ($this->expectedIsContainer) {
            $this->subItemList->verifyIsLoaded();
        }

        Assert::assertEquals(
            $this->expectedContentName,
            $this->getHTMLPage()->find($this->getLocator('pageTitle'))->getText()
        );

        Assert::assertEquals(
            $this->expectedContentType,
            $this->getHTMLPage()->find($this->getLocator('contentType'))->getText()
        );
    }

    public function getName(): string
    {
        return 'Content view';
    }

    public function editContent(?string $language)
    {
        $this->rightMenu->clickButton('Edit');

        if ($this->languagePicker->isVisible()) {
            $availableLanguages = $this->languagePicker->getLanguages();
            Assert::assertGreaterThan(1, count($availableLanguages));
            Assert::assertContains($language, $availableLanguages);
            $this->languagePicker->chooseLanguage($language);
        }
    }

    public function isChildElementPresent(array $parameters): bool
    {
        return $this->subItemList->isElementInTable($parameters);
    }

    public function sendToTrash()
    {
        $this->rightMenu->clickButton('Send to Trash');
        $this->dialog->confirm();
    }

    public function verifyFieldHasValues(string $fieldLabel, array $expectedFieldValues, ?string $fieldTypeIdentifier)
    {
        $this->contentItemAdminPreview->verifyFieldHasValues($fieldLabel, $expectedFieldValues, $fieldTypeIdentifier);
    }

    protected function specifyLocators(): array
    {
        return [
            new VisibleCSSLocator('pageTitle', '.ez-page-title h1'),
            new VisibleCSSLocator('contentType', '.ez-page-title h4'),
            new VisibleCSSLocator('mainContainer', '#ez-tab-list-content-location-view'),
        ];
    }

    protected function getRoute(): string
    {
        return $this->route;
    }
}