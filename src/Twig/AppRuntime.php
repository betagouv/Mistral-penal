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
namespace App\Twig;

use App\Entity\Security\Account;
use App\Entity\AffaireNatinf;
use App\Entity\Audience;
use App\Form\CalendarFilterType;
use App\Repository\AffaireNatinfRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

class AppRuntime implements RuntimeExtensionInterface
{
  const MONTH_PLAINTEXT_FULL = 'full';
  const MONTH_PLAINTEXT_SMALL= 'short';

  const ENGLISH_MONTHS = [
    'january', 'february', 'march', 'april', 'may', 'june',
    'july', 'august', 'september', 'october', 'november', 'december'
  ];

  private ?EntityManagerInterface $_em = null;
  private ?Environment $_env = null;
  private ?Request $_request = null;
  private ?FormFactoryInterface $_factory = null;
  private ?TranslatorInterface $_trans = null;
  private ?RouterInterface $_router = null;

  public function __construct(
    RequestStack $requestStack,
    TranslatorInterface $trans,
    EntityManagerInterface $em,
    Environment $env,
    FormFactoryInterface $factory,
    RouterInterface $router
  ) {
    $this
      ->setRequestFromRequestStack($requestStack)
      ->setTranslator($trans)
      ->setEnvironment($env)
      ->setFactory($factory)
      ->setEntityManager($em)
      ->setRouter($router)
    ;
  }

  public function setRouter(RouterInterface $router): self {
    $this->_router = $router;

    return $this;
  }

  public function makeDiffAffaireNatinf(AffaireNatinf $affaireNatinf): array {
    /** @var AffaireNatinf $root */
    $root = $affaireNatinf->getDuplicateRoot()??$affaireNatinf;
    return AffaireNatinfRepository::diff($affaireNatinf, $root);
  }
  public function getRouter(): ?RouterInterface {
    return $this->_router;
  }

  public function buildDate(int $day, int $month, int $year): \DateTime {
    $strDay = str_pad($day, 2, "0", STR_PAD_LEFT);
    $strMonth = str_pad($month, 2, "0", STR_PAD_LEFT);
    $strYear = str_pad($year, 4, "0", STR_PAD_LEFT);
    return new \DateTime($strYear.'-'.$strMonth.'-'.$strDay);
  }
  public function toDate(string $strDate): ?\DateTime {
    return (preg_match("/(\d{4})[-](\d{2})[-](\d{2})/", $strDate)) ? new \DateTime($strDate) : null;
  }

  public function setEntityManager(EntityManagerInterface $em): self {
    $this->_em = $em;

    return $this;
  }

  public function getEntityManager(): ?EntityManagerInterface {
    return $this->_em;
  }

  public function setFactory(FormFactoryInterface $factory): self {
    $this->_factory = $factory;

    return $this;
  }

  public function getFactory(): ?FormFactoryInterface {
    return $this->_factory;
  }

  public function setRequestFromRequestStack(RequestStack $requestStack): self {
    $this->_request = $requestStack->getCurrentRequest();

    return $this;
  }

  public function getRequest(): ?Request {
    return $this->_request;
  }

  public function setTranslator(TranslatorInterface $trans): self {
    $this->_trans = $trans;

    return $this;
  }

  public function getTranslator(): ?TranslatorInterface {
    return $this->_trans;
  }

  public function translate(string $message, array $params=[]): string {
    $tmp = $this->getTranslator()->trans($message);
    foreach($params as $key => $value)
      $tmp = str_replace($key,$value, $tmp);
    return $tmp;
  }
  public function setEnvironment(Environment $env): self {
    $this->_env = $env;

    return $this;
  }

  public function getEnvironment(): ?Environment {
    return $this->_env;
  }

  /**
   * Renvoi du mois en texte plein à partir d'un index (1: janvier)
   *
   * @param int $month
   * @param string $type
   * @return ?string
   */
  public function getMonthPlaintext(int $month, string $type=self::MONTH_PLAINTEXT_FULL): ?string {

    /** @var array $months */
    $months = self::ENGLISH_MONTHS;

    $trans = $this->getTranslator();

    if(($month < 1)||($month > 12))
      return null;

    $tmp = "globals.calendar.".$months[$month-1].".$type";

    return (null !== $trans) ? $trans->trans($tmp) : null;
  }

  public function getDateSmall(?\DateTime $date=null): string {
    if(null === $date) { $date = new \DateTime(); }
    return $date->format('d/m/y');
  }
  /**
   * Renvoi d'une date en texte plein
   *
   * @param ?\DateTime $date
   * @return string
   */
  public function getDatePlaintext(?\DateTime $date=null): string {
    if(null === $date) { $date = new \DateTime(); }
    /** @var string $frdt */
    $frdt = strftime('%d %B %Y',$date->getTimestamp());
    /** @var array $nmeng */
    $nmeng = self::ENGLISH_MONTHS;
    /** @var array $nmfr Equivalent traduit au mois anglais */
    $nmfr=[];
    /** @var ?TranslatorInterface $trans */
    $trans = $this->getTranslator();
    /** @var string $item */
    foreach($nmeng as $item) {
      /** @var string $tmp */
      $tmp = "globals.calendar.".$item.".full";
      $nmfr[]=($trans ? $trans->trans($tmp) : $tmp);
    }

    return str_ireplace($nmeng, $nmfr, $frdt);
  }

  public function getLastDay(int $year, int $month): int {
    /** @var string $strMonth */
    $strMonth = str_pad($month,2,"0",STR_PAD_LEFT);
    /** @var \DateTime $firstDate */
    $firstDate= new \DateTime("$year-$strMonth-01");
    /** @var int $lastDay */
    $lastDay  = $firstDate->format('t');
    return $lastDay;
  }

  // public function getLinesByMonth(int $year, int $month): array {
  //   /** @var string $strMonth */
  //   $strMonth = str_pad($month,2,"0",STR_PAD_LEFT);
  //   /** @var \DateTime $firstDate */
  //   $firstDate= new \DateTime("$year-$strMonth-01");
  //   /** @var int $firstPos Position du premier jour de la première ligne */
  //   $firstPos = $firstDate->format('N')-1;
  //   /** @var int $lastDay Nombre de jours pour le mois courant */
  //   $lastDay  = $firstDate->format('t');
  //   $tmp      = $lastDay+$firstPos;
  //   $num      = 1;
  //   /** @var int $lines Nombre de ligne dans le calendrier */
  //   $lines    =[];
  //   for($i=0;$i < $tmp;$i+=7) {
  //     for($dn=0;$dn<7;$dn++) {
  //       if($i == 0 && $dn < $firstPos) { $lines[$i][]=null; }
  //       elseif($num > $lastDay) { $lines[$i][]=null; }
  //       else { $lines[$i][]=$num; $num++; }
  //     }
  //   }
  //   return $lines;
  // }

  // /**
  //  * Renvoi du contexte courant
  //  *
  //  * @return array
  //  */
  // private function getCalendarContext(Account $user): array {
  //   /** @var \DateTime $now */
  //   $now = new \DateTime();
  //   /** @var int $year */
  //   $year     = (int)$now->format('Y');
  //   /** @var int $month */
  //   $month    = (int)$now->format('n');
  //   $filters  =$this->getRequest()->get('calendar_filter');
  //   /** @var string $curMonth */
  //   $tmpMonth = !empty($filters['month']) ? $filters['month'] :"";
  //   if(preg_match("/^(?<year>\d+)[-](?<month>\d+)$/i",$tmpMonth, $matches)) {
  //     $year = (int)$matches['year'];
  //     $month= (int)$matches['month'];
  //   }
  //   $strMonth = str_pad($month,2,"0",STR_PAD_LEFT);
  //   /** @var \DateTime $firstDate */
  //   $firstDate= new \DateTime("$year-$strMonth-01");
  //   /** @var bool $switchWeek */
  //   $switchWeek= ($strMonth == '01');
  //   /** @var int $lines Nombre de ligne dans le calendrier */
  //   $lines    = $this->getLinesByMonth($year, $month);
  //   /** @var int $firstWeek Numéro de la semaine */
  //   $firstWeek = (int)$firstDate->format('W');
  //   /** @var Form $form */
  //   $form = $this->getFactory()->create(CalendarFilterType::class);
  //   $form->handleRequest($this->getRequest());
  //   unset($strMonth);
  //   /** @var array<int, Audience> $ios */
  //   $ios = $this
  //     ->getEntityManager()
  //     ->getRepository(Audience::class)
  //     ->findByDateAndUser($firstDate, $user)
  //     ->getResult()
  //   ;
  //   /** @var array $output Liste des audiences trouvées */
  //   $output = [];
  //   /** @var Audience $item */
  //   foreach($ios as $item) {
  //     $date = (int)$item->getDate()->format("d");
  //     $output[$date][]=$item;
  //   }

  //   return [$now, $year, $month, $firstDate, $firstWeek, $switchWeek, $lines, $form, $output];
  // }

  // public function convertAudienceToLink(Audience $ios): string {
  //   /** @var TranslatorInterface $trans */
  //   $trans = $this->getTranslator();

  //   $url = $this
  //     ->getRouter()
  //     ->generate("audience_show", ['id' => $ios->getId()]);
  //   ;
  //   /** @var ?string $plaintext */
  //   $plaintext = $ios->getPlaintext($trans);
  //   return $this
  //     ->getEnvironment()
  //     ->render('calendar/iteration/audience_link.html.twig', [
  //       'url' => $url,
  //       'plaintext' => $plaintext,
  //       'audience' => $ios,
  //     ]);
  // }

  // /**
  //  * @param Account $user
  //  * @param ?string $title
  //  */
  // public function getCalendarAudiences(Account $user, ?string $title=null) {
  //   /**
  //    * @var \DateTime $now
  //    * @var int $year
  //    * @var int $month
  //    * @var \DateTime $firstDate
  //    * @var int $firstWeek
  //    * @var bool $switchWeek
  //    * @var array $lines
  //    * @var Form $form
  //    * @var array $audiences
  //    */
  //   list($now, $year, $month, $firstDate, $firstWeek, $switchWeek, $lines, $form, $audiences) = $this->getCalendarContext($user);

  //   return $this
  //     ->getEnvironment()
  //     ->render('calendar/audiences.html.twig', [
  //       'title' => $title,
  //       'lines' => $lines,
  //       'firstWeek' => $firstWeek,
  //       'monthPlaintext' => $this->getMonthPlaintext($month),
  //       'year' => $year,
  //       'switchWeek' => $switchWeek,
  //       'audiences' => $audiences,
  //       'form'      => $form->createView(),
  //     ]);

  // }

}
