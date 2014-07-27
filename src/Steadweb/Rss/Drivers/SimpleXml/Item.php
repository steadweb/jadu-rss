<?php

namespace Steadweb\Rss\Drivers\SimpleXml;

use Steadweb\Rss\Interfaces\FeedItemInterface;

class Item implements FeedItemInterface
{
	/**
	 * @var
	 */
	protected $title;

	/**
	 * @var
	 */
	protected $uri;

	/**
	 * @var
	 */
	protected $description;

	/**
	 * @var
	 */
	protected $published;

	/**
	 * Create a new Feed\Item
	 *
	 * @param $title
	 * @param $uri
	 * @param null $description
	 * @param null $published
	 */
	public function __construct($title, $uri, $description = null, $published = null)
	{
		$this->title = $title;
		$this->uri = $uri;
		$this->description = $description;
		$this->published = $published;

		return $this;
	}

	/**
	 * Get the title of the feed item
	 *
	 * @return mixed
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Get the URI of the feed item
	 *
	 * @return mixed
	 */
	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * Get the description or provide a default of the feed item
	 *
	 * @param null $default
	 * @return mixed
	 */
	public function getDescription($default = null)
	{
		return $this->description;
	}

	/**
	 * Get the publish date of the item
	 *
	 * @param null $format
	 * @return mixed
	 */
	public function getPublishDate($format = null)
	{
		return $this->published;
	}
}