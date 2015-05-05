# Introduction #

The Tuxxedo Engine provides its own full blown Templating engine. Like many others, this templating system have two stages, a compilation stage and a execution stage. A template is compiled before it can be used by the Engine.

# Compilation #

The compilation stage translates (compiles) the easy readable markup language into PHP executable code, that can either be stored at the file system (.raw & .tuxx files) or at the database.

The compilation process is really heavy and slow, but in the end optimizes the code so its much faster, and better encapsulated. Due to the nature of a non-interpreted compiler, all templates are required to be compiled before they can successfully be used within the user land code (execution).

Below demonstrates how the compiler is used, alternatives with a GUI interface do exists (/dev/tools/tools.php?do=compiler). The compiler is non style depended, and compiles a template at the time, this is done by the following:

```
<?php
/* Bootstraper */
require('./library/bootstrap.php');

/* Namespace aliases */
use Tuxxedo\Exception;
use Tuxxedo\Template;
use Tuxxedo\Template\Compiler;

/* Sample markup code, contains a condition and a form */
$markup = <<<'MARKUP'
<if expression="isset($name)">
        Hello {$name}
<else />
        <form action="./" method="post">
                <p>Enter your name: <input type="text" name="name" /></p>
                <input type="submit" value="Greet" />
        </form>
</if>
MARKUP;

/* Start the compiler and tell it what markup to compile */
$compiler = new Template\Compiler;
$compiler->set($markup);

try
{
        /* Attempt to compile the template, this may throw an exception */
        $compiler->compile();

        /* Test if the compiled template worked, this prevents fatal errors at runtime */
        if(!$compiler->test())
        {
                throw new Exception\TemplateCompiler('Syntax error while attempting to execute compiled code');
        }
}
catch(Exception\TemplateCompiler $e)
{
        /* Simple wrapper for outputting the compilation error */
        tuxxedo_doc_error($e);
}

/* Done, show the differences */
echo('<h2>Original markup</h2>');
echo('<pre>');
echo(htmlspecialchars($markup));
echo('</pre>');

echo('<h2>Compiled markup</h2>');
echo('<pre>');
echo(htmlspecialchars($compiler->get()));
echo('</pre>');
?>
```

Executing the above will show something similar to:

### Original markup ###
```
<if expression="isset($name)">
        Hello {$name}
<else />
        <form action="./" method="post">
                <p>Enter your name: <input type="text" name="name" /></p>
                <input type="submit" value="Greet" />
        </form>
</if>
```

### Compiled markup ###
```
" . ((isset($name)) ? ("
        Hello {$name}
") : ("
        <form action=\"./\" method=\"post\">
                <p>Enter your name: <input type=\"text\" name=\"name\" /></p>
                <input type=\"submit\" value=\"Greet\" />
        </form>
")) . "
```

As you can see the PHP variables are still there, the conditionals have been converted into ternary expressions and all double quotes have been escaped to allow variable interpolation (which is how the executed template is performed, but more about that later).

Special characters like \t would normally get convert into 0x9 (horizontal tab) but the compiler is smart enough to skip all the special characters that can be interpolated within regular PHP strings.

## Special compilation rules ##

When the compiler hits an expression, it will look if there is a function call, or if there is a method call.

By default, only the follow small subset of functions (procedural) are allowed: defined(), sizeof() & count(). The empty() and isset() language constructs are allowed too. To allow custom functions, simply call $compiler->allowFunction($function\_name);

Special verbose operators, etc are allowed too: AND, OR, XOR & Array().

Methods are a bit tricky, since the compiler cannot figure out what instance $user refers to in this context: $user->isLoggedIn(). Then you must explicitly tell the compiler that $user is an object, so it can emulated it when calling the test(), and when checking if the instance is allowed within an expression call. By default the compiler allows the following two instances to be allowed within templates, as they are the most common: $user & $usergroup.

Lets have a quick example, our markup looks like this:
```
$markup = <<<'MARKUP'
<if expression="($price = $product->getPrice()) !== false">
        Product price is: {$price} dollars
</if>
MARKUP;
```

When compiling, it will tell us that $product->getPrice() is not allowed ($product is not an allowed instance). To do so, we will have to call $compiler->allowClass($class\_name); This should be called like $compiler->allowClass('product'); and then the compilation method. Note that once an instance is allowed, all method calls are allowed, any regular visibility rules applies here.

Closures, are allowed too, either where a variable is assigned to a callback, or if a variable is an instance of the Closure class. These can be defined by the allowClosure() method of the compiler class.

# Execution #

Execution is done by eval() statements, to allow variable interpolation.

If we use the following markup code again:
```
$markup = <<<'MARKUP'
<if expression="isset($name)">
        Hello {$name}
<else />
        <form action="./" method="post">
                <p>Enter your name: <input type="text" name="name" /></p>
                <input type="submit" value="Greet" />
        </form>
</if>
MARKUP;
```

Once that is stored within a storage engine, we can now fetch it. Note that as of writing, we do not provide an internal API for modifying templates, but its subject to the next major release of the Engine. But lets suggest that we stored the above compiled markup in a template named 'greeting'.

### Loading ###

There is two ways of loading templates, pre loading and manually loading. Pre loading can be done in two ways, templates that needs to be loaded upon startup must be declared before the default bootstraper is included. The two types of pre loading are global templates, meaning templates that will be available all the time, second is action templates and is only loaded once a specific action is execute.

**Global templates**

Global templates are defined like:
```
$templates = Array('greeting', 'someother_template', ...);
```

This makes the listed templates required for the page to load.


**Action templates**

Action templates are loaded only if a specific action is executed, an action is determined by ?do=actionname:

```
$action_templates = Array(
                                'actionname' => Array('special_template1'), 
                                'other' => Array('template1', 'template2', ...)
                                );
```

If no action is defined or there is no action templates for the default action, then no templates is loaded. If we used the above example and requested ?do=actionname, then besides the global templates if any requested, the 'special\_template1' would be loaded too.


**Manual loading**

Manual loading are only available once the bootstraper is done executing, this is done by calling the style object's cache method like so:

```
$style->cache(Array('template1', 'template2', ...));
```

To handle errors here, the Engine provides a simple way to see which template that failed to load, one at the time:

```
$error_buffer = Array();
$style->cache(Array('template1', 'template2'), $error_buffer) or tuxxedo_multi_error('Failed to load template named \'%s\'', $error_buffer);
unset($error_buffer);
```


## Template assignments ##

Because of the design of the template engine, templates are essentially just PHP variables that can be interpolated. This means that you can define common templates like a header or footer so they can be included into a full page template, this can be done like so:

```
/* Set header and footer */
eval('$header = "' . $style->fetch('header') . '";');
eval('$footer = "' . $style->fetch('footer') . '";');

/* Later set the page content */
eval(page('test'));
```

The above example defines two new variables, $header and $footer with the content of each respective contents as variables so they can be replaced within another template. The magic page function is essentially just an echo statement, but it can be used in any context as it will inherit two special variables, $header and $footer.

Note that common templates such as header and footer should be defined within the bootstraper.

Lets look at what the script would produce, here are the template contents:

**header**
```
<html>
<head>
<title>Hello World</title>
</head>
<body>
```

**footer**
```
</body>
</html>
```

**test**
```
{$header}

<p>Hello world</p>

{$footer}
```

and the final result would be:
```
<html>
<head>
<title>Hello World</title>
</head>
<body>
<p>Hello world</p>
</body>
</html>
```

## Errors, redirects, ... ##

Engine have something thats internally called 'gui mode', this means once the \Tuxxedo\Style class have been loaded and the bootstraper is done, it will be in GUI mode, meaning that its possible to show errors using user defined templates.

throwing a new \Tuxxedo\Exception will call tuxxedo\_gui\_error() and procedure an error using the defined theming.

To redirect, call tuxxedo\_redirect() and the user is prompted to be redirected.


Note that these two special conditions are called within their own scope, so its not possible to inherit variables like {$test} within the templates and must be called like {$GLOBALS['test']}.

## Variables ##

As written in the sample header/footer example, then variables are PHP variables and can be inherited, like so:

**Markup**
```
<p>Hello {$name}</p>
```

**Code**
```
$name = 'Kalle';

eval(page('greeting'));
```

Prints:
```
<p>Hello Kalle</p>
```

## Concatenated templates ##

Applications that needs to output lists of data, like search results you will need to concatenate templates. This is done using the assign-concatenate operator in PHP like so:

```
/* Must be here, to prevent notices when iterating */
$search_results = '';

foreach($search->getResults() as $result)
{
        eval('$search_results .= "' . $style->fetch('search_results_row') . '";');
}

eval(page('search_results'));
```

The templates used, could looks like this to show how it works:

**search\_results\_row**
```
<li>{$result}</li>
```

**search\_results**
```
<h3>Search results</h3>

<ul>
{$search_results}
</ul>
```

And the final output will be:
```
<h3>Search results</h3>

<ul>
<li>Practical PHP Programming</li>
<li>PHP Programming</li>
</ul>
```

Now lets take this example a bit further to illustrate how expressions also can go into the mix. This is out script code, note that the Search class just for the sake of this example and is not real:
```
/* Search all books named something with PHP in their title */
$search = new Search('books', 'PHP', Array('title'));

if(!$search->getNumResults())
{
        throw new \Tuxxedo\Exception('No books matched PHP in their title');
}

$results = '';

foreach($search->getResults() as $result)
{
        /* str_replace('PHP', '<b>PHP</b>', $result->title); */
        $result->title = $search->highlight('<b>%s</b>', $result->title);

        eval('$results .= "' . $style->fetch('search_results_row') . '";');
}

eval(page('search_results'));
```

The search\_results template here is still the same, and the 'search\_results\_row' template have been changed to the following:
```
<li>
<if expression="!$result->in_stock">
<span style="color: red;">
</if>

{$result->title} by <em>{$result->author}</em>

<if expression="!$result->in_stock">
</span>
</if>
</li>
```

As you can see the markup is a lot more _fancy_ and more dynamic.

## Quotes! ##

Quotes are very important within the execution stage, as you might remember then all double quotes are escaped by the compiler. This is because PHP uses double quotes for variable interpolation within strings.

This means that the eval() statement must always looks like this:
```
eval('$something = '"' . $style->fetch('something_template') . '";');
```

Which converted into:
```
$number = 42;
$something = "This is the something template with a variable, that have the value of {$number}";
```

So $something now have the value of:
```
This is the something template with a variable, that have the value of 42
```