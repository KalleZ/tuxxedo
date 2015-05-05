# Reporting information from the Engine and Application #

There are two ways the application can report back to the user - blocking ("I could not proceed") and non-blocking ("just letting you know"). In our case errors (exceptions) block the application from continuing and messages just give information.

```
    Exception     Message
        |          /   \
        v         v    v
      Error  Warning  Information
```

There are two types of message - warning and information - based on the severity of the situation. For example, that the user visited a link that led to a 404 (but was safely given an error page) is a warning (and not an error), while the user successfully logging in is an informational message.

The application can choose to pass on caught errors and messages to the user or take other action.

## Errors ##

At the moment each component can add it's own exception class or use something like `Tuxxedo_Exception_Basic`. Since these don't tend to add anything (perhaps some custom properties) it could be better to have:

  * Some generalised exceptions (`FileNotFound`, `InvalidArgument` etc.)
  * A much more generalised exception that allows custom properties and a tag to identify it
    * For example, `throw new Tuxxedo_Exception("Controller", "Invalid controller specified") (tag, message)`
  * Components can still add their own exceptions, but for simple messages they can use the default `Tuxxedo_Exception`.

### Generalised/Common Exceptions ###

#### InvalidArgument ####

InvalidArgument would be thrown when an argument is not of the right **type**.

  * Scalar values should be converted using the appropriate type hint (e.g. `(int), (string)`) and if they are still invalid (e.g. a resulting int that must be > 0) then this exception should be thrown.
  * If you were expecting non-scalar values (arrays and objects) they should be declared in the method declaration so that PHP handles the error.
  * Generally, resources should not be encapsulated inside the object. If you are building an object based on a resource throw this if the argument does not match the needed resource type.

```
# throw new InvalidArgument($requiredType, $givenValue);
throw new InvalidArgument(InvalidArgument::INTEGER, $myValue);
```

#### FileNotFound ####

Used when a local file is not found. Examples include the autoloader, Template classes, Config loaders.

```
throw new FileNotFound($path);
```

### General Error ###

This is used where you would usually throw an `Exception` or `Exception\Basic` (or use an exception simply extending one of them with no changes).

```
# throw new Exception\General($tag, $message);
throw new Exception\General(__CLASS__, "Password must contain at least one non-alphanumeric character.");
```

## Messages ##

### HTTP Status ###

For reporting 404s, redirects etc. Can use different severities based on code (4xx == warning, 3xx = information etc.).

### Authentication ###

Report successful and unsuccessful authentication attempts.

# Logging #

Both Errors and Messages should have a log() method that calls a Logger to record the entry. The user chooses where the logger should log the attempt and which severities they'd like to record. The log could be accessed in a log-viewer dev tool.