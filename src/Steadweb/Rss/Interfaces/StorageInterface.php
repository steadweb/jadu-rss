<?php

namespace Steadweb\Rss\Interfaces;

interface StorageInterface
{
	public function __construct($host, $database, $username, $password, $options = []);
	public function save(FeedInterface $feed);
	public function remove(FeedInterface $feed);
	public function get();
}