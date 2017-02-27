<?php

namespace App\Funds\Model\Fund;

use App\Funds\Model\Fund\Exceptions\FundNotFoundException;
use Kdyby\Doctrine\EntityManager;

class FundRepository
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
	 * @param int $id
	 * @return FundEntity
	 * @throws FundNotFoundException
	 */
	public function getFund($id)
	{
		try
		{
			return $this->entityManager->createQueryBuilder()
				->select('fund')
				->from(FundEntity::class, 'fund')
				->where('fund.id = :id')
				->setParameter('id', $id)
				->getQuery()->getSingleResult();
		}
		catch (\Doctrine\ORM\NoResultException $ex)
		{
			throw new FundNotFoundException($id, $ex);
		}
		
	}
}