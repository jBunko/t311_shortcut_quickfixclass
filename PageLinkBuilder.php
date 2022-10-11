<?php

declare(strict_types=1);

namespace {VENDOR}\{EXTENSION}\Xclass;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class PageLinkBuilder extends \TYPO3\CMS\Frontend\Typolink\PageLinkBuilder
{

    /**
     * Resolves page and if a translated page was found, resolves that to its
     * language parent, adjusts `$linkDetails['pageuid']` (for hook processing)
     * and modifies `$configuration['language']` (for language URL generation).
     *
     * @param array $linkDetails
     * @param array $configuration
     * @param bool $disableGroupAccessCheck
     * @return array
     */
    protected function resolvePage(array &$linkDetails, array &$configuration, bool $disableGroupAccessCheck): array
    {
        $page = [];
        $pageRepository = $this->buildPageRepository();

        $langAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
        $isDefaultLang = $langAspect->get('id') === 0;

        // JB: Check if overlay is necessary or not
        if (!$isDefaultLang) {
            $langId = isset($configuration['language']) ? intval($configuration['language']) : $langAspect->get('id');

            // JB: Good ol' connection pool to the rescue, this is how I ensures we'll fetches the correct data
            // JB: Probably not the cleanest solution, but definitely a reliable one
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('pages')->createQueryBuilder();


            // TODO: Check for fallbacks if necessary .... well, it's not in my case
            $queryBuilder->select('*')->from('pages')->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'l10n_parent', $queryBuilder->createNamedParameter($linkDetails['pageuid'])
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid', $queryBuilder->createNamedParameter($langId)
                    )
                )
            );

            $page = $queryBuilder->executeQuery()->fetchAssociative() ?: [];
        }


        // JB: If fetching of the overlay failed, or default language is set just trigger default behaviour

        if($isDefaultLang || empty($page) || !is_array($page)) {
            // Looking up the page record to verify its existence
            // This is used when a page to a translated page is executed directly.
            $page = $pageRepository->getPage($linkDetails['pageuid'], $disableGroupAccessCheck);
        }


        //JB: Continue with the usual ...





        if (empty($page) || !is_array($page)) {
            return [];
        }
        $page = $this->resolveShortcutPage($page, $pageRepository, $disableGroupAccessCheck);

        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'] ?? null;
        $languageParentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] ?? null;
        $language = (int)($page[$languageField] ?? 0);

        // The page that should be linked is actually a default-language page, nothing to do here.
        if ($language === 0 || empty($page[$languageParentField])) {
            return $page;
        }

        // Let's fetch the default-language page now
        $languageParentPage = $pageRepository->getPage(
            $page[$languageParentField],
            $disableGroupAccessCheck
        );
        if (empty($languageParentPage)) {
            return $page;
        }
        // Check for the shortcut of the default-language page
        $languageParentPage = $this->resolveShortcutPage($languageParentPage, $pageRepository, $disableGroupAccessCheck);

        // Set the "pageuid" to the default-language page ID.
        $linkDetails['pageuid'] = (int)$languageParentPage['uid'];
        $configuration['language'] = $language;
        return $languageParentPage;
    }

    /**
     * Checks if page is a shortcut, then resolves the target page directly
     */
    protected function resolveShortcutPage(array $page, PageRepository $pageRepository, bool $disableGroupAccessCheck): array
    {
        try {
            $page = $pageRepository->resolveShortcutPage($page, false, $disableGroupAccessCheck);
        } catch (\Exception $e) {
            // Keep the existing page record if shortcut could not be resolved
        }
        return $page;
    }
}
