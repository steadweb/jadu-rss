<?php

namespace Steadweb\Rss\Interfaces;

interface FeedInterface
{
	public function getFeedUri();
	public function getFeedTitle();
	public function getFeedDescription();
	public function getFeedLastUpdated();
}