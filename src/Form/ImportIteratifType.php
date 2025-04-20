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
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportIteratifType extends AbstractType
{
    private ?AppRuntime $_runtime=null;

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
      $current = new \DateTime();
      $current->add(new \DateInterval('P3M'));
      $curYear = (int)$current->format('Y');
      $curMonth= (int)$current->format('m');
      $output = [];
      for($year=2021;$year <= $curYear; $year++) {
          $maxMonth = ($year < $curYear) ? 12 : $curMonth;
          for($month=1;$month<=$maxMonth;$month++) {
            $strMonth = str_pad($month,2,"0", STR_PAD_LEFT);
            $output[$runtime->getMonthPlaintext($month)." $year"]="$year-$strMonth";
          }
      }
      return $output;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $day = $options['day'];
        /** @var string $data */
        $data = $options['fullmonth']??($now->format('Y').'-'.$now->format('m'));
        /** @var \DateTime $now */
        $now = new \DateTime();
        /** @var array $months */
        $months = $this->buildMonths();
        $id = $options['id'];

        $builder
          ->add('day',TextType::class, [
            'attr'     => [
              'class'       => 'fr-input current-day int-field',
            ],
            'data'     => $day,
            'label'    => false,
            'required' => false,
          ])
          ->add('month', ChoiceType::class, [
            'label_format' => 'invoice.filter.month',
            'mapped' => false,
            'required' => true,
            'choices' => $months,
            'data' => $data,
            'attr' => [
              'class' => 'fr-select current-month',
            ]
          ])
          ->add('id', TextType::class, [
            'data' => $id,
            'required' => true,
            'attr' => [
                'class' => 'fr-input id'
            ]
          ])
          ->add('submit', ButtonType::class, [
            'label' => 'import.iteratif.btn',
            'attr' => [
              'class' => 'fr-btn btn-submit'
            ]
          ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('day');
        $resolver->setRequired('fullmonth');
        $resolver->setRequired('id');
    }
}
