
# Quick fix for language versioned shortcuts (TYPO3 11.5.16)

# EDIT:

After reviewing this code with a clear mind - as rediculous as it sounds - this fix makes no sense at all since it's not changing anything in theory but it's an absolute mystery why it actually works. (Tested on two servers). 

I will provide a better fix in short.

## Induced Bug: 
language selection does not work properly any longer. Will fix this aswell.




## The issue:
If you are using shortcuts on multiple page languages which should also refer to different targets, you might experience that they are always linked to the target you've set up in the default language.

Use this XClass only if you need to fix this bug urgently, since there might be an official update very soon. 
Check the update log and don't forget to remove this afterwards.

## How to:
- If you're not familiar how to install XClasses, don't worry. Just drop the "PageLinkBuilder.php" file in "typo3conf/ext/{your_extension}/Classes/Xclass"
