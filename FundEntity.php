<?php

namespace App\Funds\Model\Fund;

use App\Funds\Model\Investment\InvestmentEntity;
use App\Funds\Model\Investor\InvestorEntity;
use App\Funds\Model\Payment\PaymentEntity;
use App\Funds\Model\TransferTypeProvider;
use App\Model\Currency;
use App\Model\Entities\AccountEntity;
use App\Model\Entities\TransferEntity;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Nette\InvalidArgumentException;
use Nette\InvalidStateException;

/**
 * @ORM\Entity
 * @ORM\Table(
 * 		name="funds_fund",
 * 		uniqueConstraints={
 * 			@ORM\UniqueConstraint(name="unique_title",columns={"title"})
 *      })
 * )
 */
class FundEntity
{
	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	const STATE_OPEN = 0;
	const STATE_CLOSED = 1;
	const STATE_FINISHED = 2;
	const STATE_CANCELLED = 3;

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Version
	 * @var int
	 */
	private $version;
	/**
	 * @ORM\Column(type="smallint")
	 * @var int
	 */
	private $state = self::STATE_OPEN;
	/**
	 * @ORM\OneToOne(
	 * 		targetEntity="App\Model\Entities\AccountEntity",
	 * 		cascade={"persist"}, fetch="EAGER"
	 * )
	 * @var AccountEntity
	 */
	private $account;
	/**
	 * @ORM\Column
	 * @var string
	 */
	private $title;
	/**
	 * @ORM\Column(type="text")
	 * @var string
	 */
	private $description;
	/**
	 * @ORM\Column(type="integer")
	 * @var int Number of days
	 */
	private $period;
	/**
	 * @ORM\Column(type="float")
	 * @var float
	 */
	private $interest;
	/**
	 * @ORM\Column(type="bigint")
	 * @var string
	 */
	private $targetAmount;
	/**
	 * @ORM\Column(type="bigint")
	 * @var string
	 */
	private $investedAmount = '0';
	/**
	 * @ORM\Column(type="bigint")
	 * @var string
	 */
	private $returnedAmount = '0';
	/**
	 * @ORM\Column(type="date", nullable=TRUE)
	 * @var DateTime
	 */
	private $closedAt = NULL;
	/**
	 * @ORM\Column(type="date", nullable=TRUE)
	 * @var DateTime
	 */
	private $finishedAt = NULL;
	/**
	 * @ORM\Column(type="date", nullable=TRUE)
	 * @var DateTime
	 */
	private $cancelledAt = NULL;
	/**
	 * @ORM\OneToMany(
	 * 		targetEntity="App\Funds\Model\Fund\InvestorEntity",
	 * 		mappedBy="fund", fetch="EXTRA_LAZY"
	 * )
	 * @var ArrayCollection
	 */
	private $investors;
	/**
	 * @ORM\OneToMany(
	 * 		targetEntity="App\Funds\Model\Fund\PaymentEntity",
	 * 		mappedBy="fund", fetch="EXTRA_LAZY"
	 * )
	 * @var ArrayCollection
	 */
	private $payments;
	/**
	 * @ORM\OneToOne(
	 * 		targetEntity="App\Model\Entities\TransferEntity",
	 * 		cascade={"persist"}
	 * )
	 * @ORM\JoinColumn(nullable=TRUE)
	 * @var TransferEntity
	 */
	private $depositTransfer = NULL;


	/**
	 * @param AccountEntity $account
	 */
	public function __construct(AccountEntity $account)
	{
		$this->account = $account;
		$this->investors = new ArrayCollection();
		$this->payments = new ArrayCollection();
	}

	/**
	 * @return int
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * @return AccountEntity
	 */
	public function getAccount()
	{
		return $this->account;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 * @return self
	 */
	public function setTitle($title)
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param string $description
	 * @return self
	 */
	public function setDescription($description)
	{
		$this->description = $description;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPeriod()
	{
		return $this->period;
	}

	/**
	 * @param int $period
	 * @return self
	 */
	public function setPeriod($period)
	{
		$this->period = $period;

		return $this;
	}

	/**
	 * @return float
	 */
	public function getInterest()
	{
		return $this->interest;
	}

	/**
	 * @param float $interest
	 * @return self
	 */
	public function setInterest($interest)
	{
		$this->interest = $interest;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTargetAmount()
	{
		return $this->targetAmount;
	}

	/**
	 * @param string $targetAmount
	 * @return self
	 */
	public function setTargetAmount($targetAmount)
	{
		$this->targetAmount = $targetAmount;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getInvestedAmount()
	{
		return $this->investedAmount;
	}

	/**
	 * @return string
	 */
	public function getReturnedAmount()
	{
		return $this->returnedAmount;
	}

	/**
	 * @return string
	 */
	public function getTotalExpectedReturnAmount()
	{
		$amount = $this->closedAt ? $this->investedAmount : $this->targetAmount;

		return bcmul($amount, (1.0 + $this->interest));
	}

	/**
	 * @param string $amount
	 * @return string
	 */
	public function getExpectedReturnAmount($amount)
	{
		return bcmul($amount, 1.0 + $this->interest, 0);
	}

	/**
	 * @return DateTime
	 * @throws \RuntimeException
	 */
	public function getClosedUntil()
	{
		if ($this->closedAt == NULL)
		{
			throw new \RuntimeException('The fund is not closed.');
		}

		return clone $this->closedAt->modify("+{$this->period}days");
	}

	/**
	 * @return TransferEntity
	 */
	public function getDepositTransfer()
	{
		return $this->depositTransfer;
	}

	/**
	 * @param AccountEntity $investorAccount
	 * @param string $amount
	 * @return InvestmentEntity
	 * @throws \RuntimeException
	 */
	public function addFunds(AccountEntity $investorAccount, $amount)
	{
		if ($this->state == self::STATE_OPEN)
		{
			throw new \RuntimeException('Cannot add funds. The fund is not open yet.');
		}

		$futureInvested = bcadd($this->investedAmount, $amount);

		if (bccomp($this->targetAmount, $futureInvested) == -1)
		{
			throw new InvalidArgumentException('Target amount exceeded.');
		}

		$this->investedAmount = $futureInvested;

		$investor = $this->getInvestor($investorAccount, TRUE);
		$investment = $investor->addInvestment($amount);

		$this->funded = $futureInvested;

		return $investment;
	}

	/**
	 * @param AccountEntity $investorAccount
	 * @param string $amount
	 * @return InvestmentEntity
	 * @throws InvalidStateException
	 * @throws InvalidArgumentException
	 */
	public function removeFunds(AccountEntity $investorAccount, $amount)
	{
		if ($this->state == self::STATE_OPEN)
		{
			throw new \RuntimeException('Cannot remove funds. The fund is not open yet.');
		}

		$investor = $this->getInvestor($investorAccount);

		if (!$investor)
		{
			throw new InvalidArgumentException('Invalid investor.');
		}

		$investment = $investor->addInvestment(bcmul($amount, '-1')); // -$amount
		$this->funded = bcsub($this->funded, $amount);

		return $investment;
	}

	/**
	 * @throws \RuntimeException
	 */
	public function markClosed()
	{
		if ($this->state != self::STATE_OPEN)
		{
			throw new \RuntimeException('Invalid fund state. The fund cannot be closed.');
		}
		else if (bccomp($this->investedAmount, 0) != 1)
		{
			throw new \RuntimeException('Cannot close empty fund.');
		}

		$this->closedAt = new DateTime();

		$this->depositTransfer = new TransferEntity(
			TransferTypeProvider::TYPE_DEPOSIT, $this->investedAmount, Currency::CODE_BTC
		);
		$this->depositTransfer->setState(TransferEntity::STATE_CONFIRMED);

		$this->account->addTransfer($this->depositTransfer);
	}

	/**
	 * @throws \RuntimeException
	 */
	public function markFinished()
	{
		if ($this->state != self::STATE_CLOSED)
		{
			throw new \RuntimeException('Invalid fund state. The fund cannot be finished.');
		}

		$this->finishedAt = new DateTime();
	}

	/**
	 * @throws \RuntimeException
	 */
	public function markCancelled()
	{
		if ($this->state != self::STATE_OPEN)
		{
			throw new \RuntimeException('Invalid fund state. The fund cannot be cancelled.');
		}

		$this->cancelledAt = new DateTime();

		foreach ($this->getInvestors(TRUE) as $investor)
		{
			$this->removeFunds($investor, $investor->getInvestedAmount());
		}
	}

	/**
	 * @param string $amount
	 * @return PaymentEntity
	 * @throws \RuntimeException
	 */
	public function addPayment($amount)
	{
		if ($this->state != self::STATE_CLOSED)
		{
			throw new \RuntimeException('Invalid fund state.');
		}

		$payment = new PaymentEntity($this, $amount);

		foreach ($this->getInvestors(TRUE) as $investor)
		{
			$investorAmount = bcdiv(
				bcmul($amount, $investor->getInvestedAmount()), $this->investedAmount, 0
			);

			$payment->addPayout($investor, $investorAmount);
		}

		$this->account->addTransfer($payment->getTransfer());
		$this->payments->add($payment);

		$this->returnedAmount = bcadd($this->returnedAmount, $payment->getAmount());

		return $payment;
	}

	/**
	 * @param AccountEntity $account
	 * @param boolean $createIfNotExists
	 * @return InvestorEntity
	 */
	public function getInvestor(AccountEntity $account, $createIfNotExists = FALSE)
	{
		$criteria = Criteria::create();
		$criteria->where(Criteria::expr()->eq('account', $account))
			->setMaxResults(1);

		$investor = $this->investors->matching($criteria)->first();

		if (!$investor && $createIfNotExists)
		{
			$investor = new InvestorEntity($this, $account);

			$this->investors->add($investor);
		}

		return $investor;
	}

	/**
	 * @param boolean $onlyActive
	 * @return InvestorEntity[]
	 */
	public function getInvestors($onlyActive = TRUE)
	{
		$criteria = Criteria::create();

		if ($onlyActive)
		{
			$criteria->where(Criteria::expr()->gt('investedAmount', 0));
		}

		return $this->investors->matching($criteria);
	}
}