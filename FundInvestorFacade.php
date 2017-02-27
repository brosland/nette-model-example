<?php

namespace App\Funds\Model\Fund;

use App\Funds\Model\Investment\InvestmentEntity;
use App\Model\Entities\AccountEntity;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\EventArgsList;
use Kdyby\Events\EventManager;

class FundInvestorFacade
{
	const EVENT_FUNDS_ADDED = self::class . '::onFundsAdded';
	const EVENT_FUNDS_REMOVED = self::class . '::onFundsRemoved';


	/**
	 * @var EntityManager
	 */
	private $entityManager;
	/**
	 * @var EventManager
	 */
	private $eventManager;
	/**
	 * @var FundRepository
	 */
	private $fundRepository;


	/**
	 * @param EntityManager $entityManager
	 * @param EventManager $eventManager
	 * @param FundRepository $fundRepository
	 */
	public function __construct(
	EntityManager $entityManager, EventManager $eventManager,
		FundRepository $fundRepository
	)
	{
		$this->entityManager = $entityManager;
		$this->eventManager = $eventManager;
		$this->fundRepository = $fundRepository;
	}

	/**
	 * @param int $fundId
	 * @param int $investorAccountId
	 * @param string $amount
	 * @return InvestmentEntity
	 */
	public function addFunds($fundId, $investorAccountId, $amount)
	{
		$investorAccount = $this->entityManager->getReference(
			AccountEntity::class, $investorAccountId
		);

		$fund = $this->fundRepository->getFund($fundId);

		$investment = $fund->addFunds($investorAccount, $amount);

		$this->entityManager->persist($investment);
		$this->entityManager->flush();

		$this->eventManager->dispatchEvent(
			self::EVENT_FUNDS_ADDED, new EventArgsList([$fund])
		);

		return $investment;
	}

	/**
	 * @param int $fundId
	 * @param int $investorAccountId
	 * @param string $amount
	 * @return InvestmentEntity
	 */
	public function removeFunds($fundId, $investorAccountId, $amount)
	{
		$investorAccount = $this->entityManager->getReference(
			AccountEntity::class, $investorAccountId
		);

		$fund = $this->fundRepository->getFund($fundId);

		$investment = $fund->removeFunds($investorAccount, $amount);

		$this->entityManager->persist($investment);
		$this->entityManager->flush();

		$this->eventManager->dispatchEvent(
			self::EVENT_FUNDS_REMOVED, new EventArgsList([$fund])
		);

		return $investment;
	}
}