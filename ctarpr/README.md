# CTARPR
DRUPAL 8/9 module. Content Type Access Restriction per Role

This module restricts access for certain type of content, with redirection.
It means, you can restrict access of Article content type and make redirection to some other node.
1. Go to /admin/config/system/ctarpr-settings
2. Check role(s) under certain content type what you want to restrict access and click Submit
3. Make new content under non-restricted content type and remember number of that node. That will be page where visitors with restricted access be redirected.
4. Go to /admin/config/system/ctarpr-settings again and under roles you checked, type number of node for redirection page you created.
