PHP5 Markdown
=============

A simple wiki built around the standard PHP Markdown class.

Wiki links
----------

Currently I've hacked the link handling methods in the markdown class so that relative paths are treated as wiki page references, but in all cases this relative path is treated as fixed from the wiki root. This hacking should probably be done by extending the Markdown class and overriding or wrapping the necessary methods.

So a link syntax of `[My page](myDir/myPage)` will be treated as a wiki link and linked to the page `{$wikibase}/myDir/myPage}`, so looking for a file called *myPage.markdown* in the directory *myDir* which is a sub-directory of the document directory.

------

To-do:
------

* Specifying a stylesheet
* Extract topmost header in document for use as a title
* Error message handling
* Documentation of install
* Version control. Choice between git and self-versioning
* Override layout rendering with templates
* Solve mod_rewrite baseUrl - maybe an extra config?
* History and rollback
* Allow translations of interface (how are we doing UTF-8 wise?)
* Search
* Recent changes page
* Meta information: categorising, tagging, document title, author
* Improve test coverage of MarkdownWiki class
* Tighter/more secure file-update/conflict checking
* Documentation of layout templates / accessible data structures



Wish list:
----------

* REST-based API that deals with raw markdown
* Figure out a better way of extending the base markdown class.


Things to consider:
-------------------

* Authentication / login
* Export/import markdown documents
* sub-content / shared modules
* Navigation items
* Other text-format types ( textframe or textile )


