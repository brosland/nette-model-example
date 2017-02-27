<?php

namespace App\Funds\Model\Fund\Exceptions;

class FundNotFoundException extends \RuntimeException
{
	/**
	 * @var int
	 */
	private $fundId;


	/**
	 * @param int $fundId
	 * @param \Throwable $previous
	 */
	public function __construct($fundId, \Throwable $previous)
	{
		$message = sprintf("The fund %d was not found.", $fundId);

		parent::__construct($message, 0, $previous);

		$this->fundId = $fundId;
	}

	/**
	 * @return int
	 */
	public function getFundId()
	{
		return $this->fundId;
	}
}