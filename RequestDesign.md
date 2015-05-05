# Introduction #

The Request object holds information about what the page needs to do. For example, in a HTTP request a URI is passed holding the desired location, a custom request for unit testing might hold which action to test.

# Ideas #

## HTTP Request ##

  * User Agent - parse using a library/custom script to have easily accessible information about the user's browser
    * Perhaps have a custom analytics-style JavaScript that helps populate this (would be after the page has been sent however)
  * Request time
  * Language
  * Content-type - for returning alternate data (rss for example)
  * Caching controls