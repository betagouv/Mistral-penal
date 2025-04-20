<?php
/**
 * MIT License
 * 
 * Copyright (c) 2025 Mistral pénal - Incubateur du Minitère de la Justice
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace App\Repository\Security;

use App\Entity\Security\Account;
use App\Entity\Security\AccountService;
use App\Entity\Security\Service;
use App\Service\CryptologyHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Utils\Env;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Account>
 *
 * @method Account|null find($id, $lockMode = null, $lockVersion = null)
 * @method Account|null findOneBy(array $criteria, array $orderBy = null)
 * @method Account[]    findAll()
 * @method Account[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function hasPasswordExpired(Account $account): bool
    {
      /** @var int $durationInMonth */
      $durationInMonth = Env::get('ADMIN_PASSWORD_VALIDITY_IN_MONTH');
      /** @var \DateTime $lastDayForChangementMDP */
      $lastDayForChangementMDP = (new \DateTime())
        ->sub(new \DateInterval('P'.$durationInMonth.'M'));
      /** @var ?\DateTime $dateMDP */
      $dateMDP = $account->getDateChangementMDP();
      return(
        (null === $dateMDP)
        ||
        ($dateMDP < $lastDayForChangementMDP)
      );
    }

    public function findOneByUsername(string $username): ?Account
    {
        return $this->findOneBy(['username_hash' => CryptologyHelper::pepperedHash($username)]);
    }

    /**
     * Génération d'un mnémo aléatoire
     *
     * @return string
     */
    public static function generate_random_mnemo(): string
    {
      return mb_strtoupper('MNEMO_'.str_pad(uniqid(),13,"0", STR_PAD_LEFT));
    }

    public function save(Account $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Account $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function add(Account $account, bool $flush=false): void {
      $em = $this->getEntityManager();
      $em->persist($account);
      if(true === $flush)
        $em->flush();
    }

    public function findOneByEmail(string $email): ?Account
    {
        $accounts = $this->findAll();
        $email = trim(mb_strtolower($email));
        foreach($accounts as $account) {
          $tEmail = trim(mb_strtolower($account->getEmail()));
          if($tEmail == $email)
            return $account;
        }
        return null;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface|UserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Account) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }

    public function insertOrUpdate(array $userInformations, $getJuridictionsFunc): ?Account {
      /** @var ServiceRepository $sr */
      $sr = $this->getEntityManager()->getRepository(Service::class);
      /** @var AccountServiceRepository $asr */
      $asr = $this->getEntityManager()->getRepository(AccountService::class);
      /** @var string $username */
      $username = $userInformations['idCassiopee'] ?? null;
      if(null === $username)
        return null;

      $account = $this->findOneByUsername($username);
      if(null === $account) {
        $account = new Account();
        $account->setUsername($username);
      }
      /** @var ?string $role */
      $role = $userInformations['fonction']??null;
      if(preg_match("/^greffi/",$role))
        $account->addRole(Account::ROLE_GREFFE);

      $civility = (strtolower($userInformations['civilite']) === Account::CIVILITY_MAN) ? Account::CIVILITY_MAN : Account::CIVILITY_WOMAN;

      $account->setIdKsp($userInformations['identifiantUtilisateur']??null);
      $account->setFirstname($userInformations['prenom']??null);
      $account->setSecondFirstname($userInformations['prenom2']??null);
      $account->setLastname($userInformations['nom']??null);
      $account->setEmail($userInformations['email']??null);
      $account->setMnemo($userInformations['mnemo']??null);
      $account->setFunction($role);
      $account->setPassword("*** PASSWORD ***");
      $account->setTitle($userInformations['titre']??null);
      $account->setGrade($userInformations['corps']??null);
      $account->setCivility($civility);
      $this->save($account, true);

      $juridictions = null;

      /** @var array $services */
      $services = $userInformations['services'];
      /** @var array $serviceData */
      foreach($services as $serviceData) {
        /** @var ?bool $replace */
        $replacement=$serviceData['remplacement'];
        /** @var ?string $libelle */
        $libelle = $serviceData['libelle'] ?? null;
        /** @var ?string $mnemo */
        $mnemo = $serviceData['mnemo'] ?? null;
        if((null === $libelle)||(null === $mnemo))
          continue;
        /** @var ?\DateTime $dateStart */
        $dateStart = !empty($serviceData['date_debut']) ? new \DateTime($serviceData['date_debut']) : new \DateTime();
        /** @var ?\DateTime $dateEnd */
        $dateEnd = !empty($serviceData['date_fin']) ? new \DateTime($serviceData['date_fin']) : null;
        /** @var string $slug */
        $slug = Service::generateSlug($libelle);
        /** @var ?int $code */
        $code = !empty($serviceData['code']) ? $serviceData['code'] : null;
        /** @var ?int $numero */
        $numero = !empty($serviceData['numero']) ? $serviceData['numero'] : null;
        $service = $sr->findOneBy(['idKsp' => $serviceData["serviceId"]]);
        if(null === $service) {
          $service = new Service();

        }
        $service->setLabel($libelle);
        $service->setCode($code);
        $service->setNumero($numero);
        $service->setMnemo($mnemo);
        $service->setDateStart($dateStart);
        $service->setDateEnd($dateEnd);
        $service->setIdKsp($serviceData['serviceId']);
        $service->setJuridiction($userInformations["juridiction"]);

        if (empty($service->getTribunal())) {
            if ($juridictions === null) {
                $juridictions = $getJuridictionsFunc();
            }
            foreach ($juridictions as $juridiction) {
                foreach ($juridiction["services"] as $juridictionService) {
                    if ($juridictionService["id_ksp"] === $service->getIdKsp()) {
                        $service->setTribunal($juridiction["libelle"]);
                        break ;
                    }
                }
            }
        }

        $sr->save($service, true);
        /** @var ?AccountService $accountService */
        $accountService = $asr->findOneBy(['account' => $account, 'service' => $service]);
        if(null === $accountService) {
          $accountService = new AccountService();
          $accountService
            ->setAccount($account)
            ->setService($service)
          ;
        }
        $accountService->setReplacement($replacement);
        $asr->save($accountService, true);
      }

      return $account;
    }
}
