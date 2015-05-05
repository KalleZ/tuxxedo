# Brief #
Create an autoloader that can load namespaced (and pseudo-namespaced) classes. The loader must:

  * Be able to find a path for the class, based on it's name
  * Be based on include\_path for accessibility to users
  * Be useful to the user for loading their own classes
    * For example, in an MVC app have the loader autoload models, controllers etc. after being given a path.
  * Be efficient
  * Be backwards compatible, so that it can be used to load other libraries
    * For example, alter the class name separator to allow for pseudo-namespaced classes (e.g. `Zend_Db`);

# Tuxxedo Classes #
Class in the Tuxxedo library should follow these rules:

Assume the library is installed in the path `/library/Tuxxedo/`.

| **Class** | **Path** |
|:----------|:---------|
| Tuxxedo\Registry | Tuxxedo/Registry.php |
| Tuxxedo\Db\Adapter\MySQLi | Tuxxedo/Db/Adapter/MySQLi.php |

Classes should be organised into components (using namespaces and directories as above) except in special cases (for example, the Loader, Registry etc.).

Interfaces should be named differently where possible, for example an interface for a class named `Tuxxedo\Db\Adapter` could be `Tuxxedo\Db\Adaptable`.

## Todo ##
Rename all classes to these rules.

# Implementation #

http://gist.github.com/511804

## Usage ##
```
spl_autoload_register("Tuxxedo\Loader::load");

set_include_path(get_include_path() . PATH_SEPARATOR . "./library/");

// Load a class using the default rules
$registry = new Tuxxedo\Registry;

// Load a class from an unwatched library, using "_" as a separator
Tuxxedo\Loader::addPath("Zend", "~/ZendFW/library/Zend", "_");
$
```

## Efficiency and optimisations ##
The Load operation looks something like this:
  1. Check the name against each registered namespace match.
    * O(n) - linear to the amount of registered matches
  1. Generate a partial path
  1. Try and get the absolute path from that partial path by checking if the file exists in include\_path.
    * This is also O(n).
    * If there was a namespace match and it matched the name of the class we do not do this.

In the worst case, we have to look through all of the registered matches and the whole include\_path. To remedy this we can:
  * Configure the `include_path`.
    * Absolute paths should be used wherever possible. This will mean that we skip the second O(n) loop completely. This can be done with `realpath`.
    * The path to the most commonly used library should be first. If all your libraries are in the same /library folder and are reachable by the default router the first path should be to /library.
    * References to `/usr/share/php` and `/usr/share/pear` should be next. They could be removed if you do not use PEAR. These classes will not be autoloaded as the names are not namespaced (classes like `System` for example).
    * "." should be at the end of the include\_path. It could be removed if you're not using classes in the current directory (unlikely, especially not in an MVC app).