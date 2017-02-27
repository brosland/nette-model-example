<?php

namespace App\Funds\Model\Fund;

use App\Funds\Model\Fund\Exceptions\FundTitleNotUniqueException;
use App\Funds\Model\Payment\PaymentEntity;
use App\Model\Entities\IdentityEntity;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\EventArgsList;
use Kdyby\Events\EventManager;
use Nette\Application\ForbiddenRequestException;
use Nette\Security\User;

class FundFacade
{
	const EVENT_FUND_CREATED = self::class . '::onFundCreated';
	const EVENT_FUND_CLOSED = self::class . '::onFundClosed';
	const EVENT_FUND_FINISHED = self::class . '::onFundFinished';
	const EVENT_FUND_CANCELLED = self::class . '::onFundCancelled';
	const EVENT_PAYMENT_CREATED = self::class . '::onPaymentCreated';


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
	 * @var FundService
	 */
	private $fundService;
	/**
	 * @var User
	 */
	private $user;


	/**
	 * @param EntityManager $entityManager
	 * @param EventManager $eventManager
	 * @param FundRepository $fundRepository
	 * @param FundService $fundService
	 * @param User $user
	 */
	public function __construct(
	EntityManager $entityManager, EventManager $eventManager,
		FundRepository $fundRepository, FundService $fundService, User $user
	)
	{
		$this->entityManager = $entityManager;
		$this->eventManager = $eventManager;
		$this->fundRepository = $fundRepository;
		$this->fundService = $fundService;
		$this->user = $user;
	}

	/**
	 * @param array $values
	 * @return FundEntity
	 */
	public function createFund(array $values)
	{
		$fund = $this->fundService->createFund($values);

		$this->entityManager->persist($fund);

		try
		{
			$this->entityManager->flush();
		}
		catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex)
		{
			if ($ex->getCode() == '23000' &&
				preg_match("%key 'unique_funds_fund_title'%", $ex->getMessage()))
			{
				throw new FundTitleNotUniqueException($fund->getTitle(), $ex);
			}

			throw $ex;
		}

		$this->eventManager->dispatchEvent(
			self::EVENT_FUND_CREATED, new EventArgsList([$fund])
		);

		return $fund;
	}

	/**
	 * @param int $fundId
	 * @param array $values
	 */
	public function updateFund($fundId, array $values)
	{
		$fund = $this->fundRepository->getFund($fundId);

		$this->fundService->updateFund($fund, $values);

		try
		{
			$this->entityManager->flush();
		}
		catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex)
		{
			if ($ex->getCode() == '23000' &&
				preg_match("%key 'unique_funds_fund_title'%", $ex->getMessage()))
			{
				throw new FundTitleNotUniqueException($fund->getTitle(), $ex);
			}

			throw $ex;
		}
	}

	/**
	 * @param int $fundId
	 * @throws ForbiddenRequestException
	 */
	public function closeFund($fundId)
	{
		$fund = $this->fundRepository->getFund($fundId);

		$identity = $this->user->getIdentity();
		/* @var $identity IdentityEntity */

		if (!$identity->getAccount() != $fund->getAccount())
		{
			throw new ForbiddenRequestException();
		}

		$fund->markClosed();

		$this->entityManager->flush();

		$this->eventManager->dispatchEvent(
			self::EVENT_FUND_CLOSED, new EventArgsList([$fund])
		);
	}

	/**
	 * @param int $fundId
	 * @throws ForbiddenRequestException
	 */
	public function finishFund($fundId)
	{
		$fund = $this->fundRepository->getFund($fundId);
		$fund->markFinished();

		$identity = $this->user->getIdentity();
		/* @var $identity IdentityEntity */

		if (!$identity->getAccount() != $fund->getAccount())
		{
			throw new ForbiddenRequestException();
		}

		$this->entityManager->flush();

		$this->eventManager->dispatchEvent(
			self::EVENT_FUND_FINISHED, new EventArgsList([$fund])
		);
	}

	/**
	 * @param int $fundId
	 * @throws ForbiddenRequestException
	 */
	public function cancelFund($fundId)
	{
		$fund = $this->fundRepository->getFund($fundId);

		$identity = $this->user->getIdentity();
		/* @var $identity IdentityEntity */

		if (!$identity->getAccount() != $fund->getAccount())
		{
			throw new ForbiddenRequestException();
		}

		$fund->markCancelled();

		$this->entityManager->flush();

		$this->eventManager->dispatchEvent(
			self::EVENT_FUND_CA, new EventArgsList([$fund])
		);
	}

	/**
	 * @param int $fundId
	 * @param string $amount
	 * @return PaymentEntity
	 * @throws ForbiddenRequestException
	 */
	public function addPayment($fundId, $amount)
	{
		$fund = $this->fundRepository->getFund($fundId);

		$identity = $this->user->getIdentity();
		/* @var $identity IdentityEntity */

		if (!$identity->getAccount() != $fund->getAccount())
		{
			throw new ForbiddenRequestException();
		}

		$payment = $fund->addPayment($amount);

		$this->entityManager->persist($payment);
		$this->entityManager->flush();

		$this->eventManager->dispatchEvent(
			self::EVENT_PAYMENT_CREATED, new EventArgsList([$payment])
		);

		return $payment;
	}
}