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
namespace App\Form;

use App\Twig\AppRuntime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CalendarFilterType extends AbstractType
{
    private ?AppRuntime $_runtime=null;

    /**
     * @var int Le nombre de mois précédant le mois actuel à afficher
     */
    private int $previousMonthCount = 6;

    /**
     * @var int Le nombre de mois succédant le mois actuel à afficher
     */
    private int $nextMonthCount = 6;

    public function __construct(AppRuntime $runtime) {
      $this->setAppRuntime($runtime);
    }

    public function setAppRuntime(AppRuntime $runtime): self {
      $this->_runtime = $runtime;

      return $this;
    }

    public function getAppRuntime(): ?AppRuntime {
      return $this->_runtime;
    }

    public function buildMonths(): array
    {
        $runtime = $this->getAppRuntime();
        $output = [];

        for ($i = $this->previousMonthCount * -1; $i <= $this->nextMonthCount; ++$i) {
            $date = new \DateTime("$i month");
            $month = $date->format("m");
            $year = $date->format("Y");
            $label = $runtime->getMonthPlaintext($month) . " $year";
            $paddedMonth = str_pad($month, 2, "0", STR_PAD_LEFT);

            $output[$label] = "$year-$paddedMonth";
        }

        return $output;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $services = ["(tous)" => ""];
        foreach($options['user']->getAccountServices() as $accountService) {
          $service = $accountService->getService();
          $services[$service->getLabel().' ('.$service->getMnemo().')']=$service->getId();
        }

        /** @var \DateTime $now */
        $now = new \DateTime();
        /** @var string $data */
        $data = $options['month']??($now->format('Y').'-'.$now->format('m'));
        $data2= $options['service']??"";

        /** @var array $months */
        $months = $this->buildMonths();
        $builder
          ->setMethod('GET')
          ->add('month', ChoiceType::class, [
            'label_format' => 'invoice.filter.month',
            'mapped' => false,
            'required' => false,
            'choices' => $months,
            'data' => $data,
            'attr' => [
              'class' => 'fr-select current-month',
            ]
          ])
          ->add('service', ChoiceType::class, [
            'label_format' => 'Service',
            'mapped' => false,
            'required' => false,
            'choices' => $services,
            'data' => $data2,
            'attr' => [
              'class' => 'fr-select current-audience',
            ]
          ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
      $resolver->setRequired('month');
      $resolver->setRequired('user');
      $resolver->setRequired("service");
    }
}
