# Quick fix for language versioned shortcuts (TYPO3 11.5.16)

## The issue:
If you are using shortcuts on multiple page languages which should also refer to different targets, you might experience that they are always linked to the target you've set up in the default language.

Use this XClass only if you need to fix this bug urgently, since there might be an official update very soon. 
Check the update log and don't forget to remove this afterwards.

## How to:
- If you're not familiar how to install XClasses, don't worry. Just drop the "PageLinkBuilder.php" file in "typo3conf/ext/{your_extension}/Classes/Xclass"

- Adjust the namespace in line 5 (check your composer.json for the "psr-4" value if you're unsure)

- Add following line at the bottom of "typo3conf/ext/{your_extension}/ext_localconf.php" and adjust here the namespace as well
 `$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Frontend\Typolink\PageLinkBuilder::class] = [  
  'className' => \{VENDOR}\{EXTENSION}\PageLinkBuilder::class  
];`

- Clear backend cache, enjoy!


## The fix:
The function "resolvePage" in class `\TYPO3\CMS\Frontend\Typolink\PageLinkBuilder` receives the page uid from the redirecting page.
Unfortunately it ignores it's language overlay completly and resolves directly the target page which is set in the language root version.

This XClass overrides "resolvePage" only by checking if there exists any language overlay and uses corresponding values for further processing.
If it's not available, standard behaviour will be executed.
