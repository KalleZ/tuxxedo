# Introduction #

Add your content here.


# File/class naming #

Each class should be prefixed with `Tuxxedo_`. From then on classes that extend another should add their name to the end of the name of the extended class. For example:

```
Tuxxedo_Router // a standalone class
Tuxxedo_Router_Uri // a class that extends Tuxxedo_Router
```

# Directory naming #

Class naming should map to the directory structure, so that in most cases you could replace the `_`'s in a class name with a `/` or `\` and end up with the path for the class.

For example:

```
Tuxxedo_Router: {ROOT}/library/Tuxxedo/Router.php
Tuxxedo_Router_Uri: {ROOT}/library/Tuxxedo/Router/Uri.php
```

In some cases there might be need for a custom path, so the loader should be geared up to accept paths for certain classes. This will also be necessary for the user to use the loader in an MVC application.

# Sample Code #

Below is a sample code listing for a loader class:

```
class Tuxxedo_Loader
{
    protected static $customPaths = array();

    // Add a custom path match for a class prefix
    // For example, User_Controller (matching User_Controller_Index) is:
    // $customPaths["User"]["Controller"] = $path;
    public static function addPath($match, $path) {
        $parts = explode("_", $match);
        foreach ($parts as $i => $part) {
            if (count($parts) == $i) {
                self::$customPaths[$part] = $path;
            } else {
                self::$customPaths[$part] = array();
            }
        }
    }

    // Attempt to autoload
    public static function __autoload($name) {
        $parts = explode("_", $name);
        
        // Check for custom matches (by partial match or exact match)
        $place =& self::$customParts;
        $useCustomPath = false;
        // For each part of the class
        for ($i = 0; $i < count($parts); $i++) {
            $place =& $place[key($parts[$i])];

            // If an entry exists for this partial match
            if (isset($place)) {
                // If it is not an array we have a path, otherwise we continue
                if (!is_array($place)) {
                    $useCustomPath = true;
                    break;
                }
            } else {
                // No match at this point (should only be encountered at the first index)
                break;
            }
        }

        // Check file exists
        $path = str_replace("_", "/", $name) . ".php";
        if ($useCustomPath) {
            $path = "$place/$path";
        }
        
        if (!$path exists) {
            throw Exception;
        }

        require_once $path;
        
        if (!class_exists($name) && !interface_exists($name)) {
            throw Exception;
        }
    }
}
```