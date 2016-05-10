# Steadweb\Rss 0.1.0 #

A simple RSS package which handles multiple feeds.

### Requirements ###

- PHP 5.4+

### Optional ###

- MySQL 5.x (a storage driver is provided out the box but this is injected)

### Installation via Composer ###

Because we're using Github, we'll need to suggest where the repo resides:

`
"repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:steadweb/rss.git"
        }
],
`

The usual `require` statement is also needed:

`require: { "steadweb/rss": "dev-master" }`

### Features

- Create multiple instances of RSS containers
- Multiple storage facilities
- Handle RSS feeds under one roof
- Delete feeds
- Update all feeds in one go

### Development Features

* Driver based
* Interface driven: `Storage`, `Feed` and `Feed\Item`
* Storage is injected (Dependency Injection) via main `Steadweb\Rss\Rss` container
* Method chaining support

### Api

Create a storage object and assign that to an instance of `Steadweb\Rss\Rss`:

```
$storage = Steadweb\Rss\Storage\Mysql($host, $database, $user, $pass);
$rss = new Steadweb\Rss\Rss($storage);

```

Now we have a storage object, we can `$rss->add($feed)` a feed (you'll need to pass an instance of a feed object in order to add it to the RSS container:

```
// new RSS feed
$feed = Steadweb\Rss\Drivers\SimpleXml('http://news.php.net/group.php?group=php.announce&format=rss');

// now we can add our feed to the RSS container
$rss->add($feed);
```

We can also add multiple feeds:

```
$feeds = [
    'http://news.php.net/group.php?group=php.announce&format=rss',
    'https://uk.news.yahoo.com/rss/uk'
]l

$rss->addMany($feeds);
```

To update our RSS container, call `$rss->update()`, in the near future, we'll support the ability to update one feed.

Finally, we can also remove a feed from the RSS container by passing an instance of a feed like so:

```
$feed = Steadweb\Rss\Drivers\SimpleXml('http://news.php.net/group.php?group=php.announce&format=rss');

$rss->remove($feed);
```

### @todo

* RSS container update single feed items
* Refactor how storage is handled and dealt with then adapting to a Feed instance
