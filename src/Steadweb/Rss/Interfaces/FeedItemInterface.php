<?php

namespace Steadweb\Rss\Interfaces;

interface FeedItemInterface
{
	/**
	 * Get the title of the feed item
	 *
	 * @return mixed
	 */
	public function getTitle();

	/**
	 * Get the URI of the feed item
	 *
	 * @return mixed
	 */
	public function getUri();


	/**
	 * Get the description or provide a default of the feed item
	 *
	 * @param null $default
	 * @return mixed
	 */
	public function getDescription($default = null);

	/**
	 * Get the publish date of the item
	 *
	 * @param null $format
	 * @return mixed
	 */
	public function getPublishDate($format = null);
}
