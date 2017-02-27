<?php

namespace App\Funds\Model\Fund\Exceptions;

class FundTitleNotUniqueException extends \RuntimeException
{
	/**
	 * @var string
	 */
	private $title;


	/**
	 * @param string $title
	 * @param \Throwable $previous
	 */
	public function __construct($title, \Throwable $previous)
	{
		$message = sprintf("The fund with title '%s' already exists.", $title);

		parent::__construct($message, 0, $previous);

		$this->title = $title;
	}

	/**
	 * @return int
	 */
	public function getTitle()
	{
		return $this->title;
	}
}