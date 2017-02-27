<?php

namespace App\Funds\Model\Fund;

use App\Model\Entities\AccountEntity;
use Kdyby\Doctrine\EntityManager;
use Nette\Utils\Validators;

class FundService
{
	/**
	 * @var EntityManager
	 */
	private $entityManager;


	/**
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @param array $values
	 * @return FundEntity
	 */
	public function createFund(array $values)
	{
		Validators::assertField($values, 'account', 'int|' . AccountEntity::class);
		Validators::assertField($values, 'title', 'string');
		Validators::assertField($values, 'description', 'string');
		Validators::assertField($values, 'period', 'int');
		Validators::assertField($values, 'interest', 'float');
		Validators::assertField($values, 'targetAmount', 'string');

		if (!$values['account'] instanceof AccountEntity)
		{
			$values['account'] = $this->entityManager->getReference(
				AccountEntity::class, $values['account']
			);
		}

		$fund = new FundEntity($values['account']);
		$fund->setTitle($values['title'])
			->setDescription($values['description'])
			->setPeriod($values['period'])
			->setInterest($values['interest'])
			->setTargetAmount($values['targetAmount']);

		return $fund;
	}

	/**
	 * @param FundEntity $fund
	 * @param array $values
	 */
	public function updateFund(FundEntity $fund, array $values)
	{
		Validators::assertField($values, 'title', 'string');
		Validators::assertField($values, 'description', 'string');
		Validators::assertField($values, 'period', 'int');
		Validators::assertField($values, 'interest', 'float');
		Validators::assertField($values, 'targetAmount', 'string');

		$fund->setTitle($values['title'])
			->setDescription($values['description'])
			->setPeriod($values['period'])
			->setInterest($values['interest'])
			->setTargetAmount($values['targetAmount']);
	}
}