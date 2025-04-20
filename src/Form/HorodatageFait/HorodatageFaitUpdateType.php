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
namespace App\Form\HorodatageFait;

use App\Entity\HorodatageFait;
use App\Entity\OperateurHorodatage;
use App\Repository\HorodatageFaitRepository;
use App\Repository\OperateurHorodatageRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HorodatageFaitUpdateType extends AbstractType {

  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('id', HiddenType::class, ['mapped' => false])
      ->add('operateur', EntityType::class, [
        'choice_label' => 'libelle',
        'label' => 'operateur_horodatage.field.operateur',
        'class' => OperateurHorodatage::class,
        'attr' => [
          'class' => 'fr-select',
        ],
        'required' => false,
        'query_builder' => function (OperateurHorodatageRepository $er): QueryBuilder {
          return $er->createQueryBuilder('u')
              ->orderBy('u.libelle', 'ASC');
        },
      ])
      ->add('date',  DateType::class, [
        // 'format' => 'dd/MM/yyyy',
        'widget' => 'single_text',
        'html5' => true,
        'label' => 'horodatage_fait.field.date',
        'attr' => [
          'class' => 'fr-input',
          'placeholder' => 'globals.date',
        ],
        'required' => false,
      ])
      ->add('heure', TimeType::class, [
           'label' => 'horodatage_fait.field.heure',
           'required' => false,
           'input_format' => 'H:i',
           'widget' => 'text',
           'placeholder' => '00',
           'attr' => [
             'class' => 'fr-input',
             'style' => 'text-align:center;'
           ],
      ])
    ;
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
  }
}
