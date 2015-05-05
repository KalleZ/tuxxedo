# Introduction #

In an MVC application data is manipulated through the use of models. In abstract terms, a model is a representation of a 'thing' - a process or an object. In our case a model is a representation of a data set. For example, in a blog application we'd have a model of a Post and a model of an Author.

# Outlining a model #

We'll take a look at our Post model first. A post has many characteristics or properties: it's title, contents, the person who wrote it and when it was published. We create a basic skeleton class looking something like this:

```
class Post
{
    var $title;
    var $contents;
    var $author;
    var $published;
}
```

When creating a model try not to think about any data structure you might already have for the model's data. A model is not a wrapper class for a database table. If we think like this we can create something that is more useful to us and with less code to write!

# Creating a model #

In Tuxxedo there is a class that helps you with creating models - extending `Tuxxedo_Model` will give you access to some pre-written `__get`, `__set` and `__call` methods that are generally common to most models you will write.

To start with there are a few changes to the model we have above:

```
class Model_Post extends Tuxxedo_Post
{
    protected $id;
    protected $title;
    protected $contents;
    protected $author;
    protected $published;
}
```

We have made each property of the model protected, as our magic methods in `Tuxxedo_Model` will be used to access them from outside of the model. While our model is not a database table, we still need to include a unique identifier for when it is saved back to the database.

You might have some validation or filter logic to apply to properties in your model. The title must be a string, for example, and might have a character limit of 180.

```
class Model_Post extends Tuxxedo_Post
{
    protected $id;
    protected $title;
    protected $contents;
    protected $author;
    protected $published;

    public function setId($id) {
        $this->id = (int) $id;
    }

    public function setTitle($title) {
        if (strlen($title) > 180) {
            throw new Exception("Post title must be shorter than 180 chars.");
        }

        $this->title = (string) $title;
    }

    public function setPublished($timestamp) {
        // Convert to a timestamp
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        if (!$timestamp) {
            throw new Exception("Invalid timestamp.");
        }

        $this->timestamp = (int) $timestamp;
    }

    public function getPublished() {
        return date("Y-m-d H:i:s", $this->published);
    }
}
```

We've added a few methods here, two setter methods and a getter method. When you try to access a property from a model extending `Tuxxedo_Model` a few things happen:

  * A check is made to see if a getter method exists, using the name `set<name>` - where `<name>` is a camel-cased name with an upper-case first character, like `setPublished`.
    * Tip: You can use methods to set pseudo-properties as it is the first check made.
  * If a method is not found, a check is made to see if a property exists in the model. If it exists this is set to the value given.
  * Finally if neither are found then a `Tuxxedo_Exception_Basic` is thrown.

This logic is executed when you run any of these (with the appropriate logic in a set or get context):

```
// Setter methods
$model->property = "value";
$model->setProperty("value");

// Getter methods
echo $model->property;
echo $model->getProperty("value");
```

Continuing with this, let's create an Author model:

```
class Model_Author extends Tuxxedo_Model
{
    protected $id;
    protected $name;

    public function setId($id) {
        $this->id = (int) $id;
    }
}
```

An advantage of models comes into light here: a model's properties can be of any type, including other models. For example, lets add a little more to the Post model:

```
class Model_Post extends Tuxxedo_Model
{
    protected $author;

    public function setAuthor($author) {
        // If $author is not an instance of Model_Author create it
        if (is_int($author)) {
            $authorModel = new Model_Author;
            $authorModel->find($author);
            $author = $authorModel;
        }

        if (!($author instanceof Model_Author)) {
            throw new Exception("Invalid author type.");
        }

        $this->author = $author;
    }
}
```

When we use find on the Post model we will have an id for the author. When we set the author property to be an id this will automatically retrieve information for the author.

# To be added #

  * Creating a data-mapper (from a model to a data source, such as a datamanager)
    * Including a Tuxxedo\_Model\_Mapper interface that requires find, fetchAll, save to be implemented.
  * Writing a datamanager(?)