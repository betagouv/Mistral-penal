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
namespace App\Form\AffairePersonne;

use App\Entity\AffairePersonne;
use App\Entity\ModeConvocation;
use App\Entity\ModeComparution;
use App\Entity\ModePoursuite;
use App\Entity\StatutPersonne;
use App\Form\AffaireNatinf\AffaireNatinfReadOnlyType;
use App\Form\Personne\PersonneType;
use App\Form\RichCheckboxType;
use App\Repository\ModeConvocationRepository;
use App\Repository\ModeComparutionRepository;
use App\Repository\ModePoursuiteRepository;
use App\Repository\StatutPersonneRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AffairePersonneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
          ->add('id', HiddenType::class, ['mapped' => false])
          ->add('personne', PersonneType::class)
           ->add('mineur',  RichCheckboxType::class, [
                'label' => 'affaire_personne.field.mineur',
                'required' => false,
           ])
           ->add('isVisio',  RichCheckboxType::class, [
                'label' => 'affaire_personne.field.is_visio',
                'required' => false,
           ])
           ->add('isVisio',  RichCheckboxType::class, [
                'label' => 'affaire_personne.field.is_visio',
                'required' => false,
           ])
           ->add('debutVisio', TimeType::class, [
                'label' => 'affaire_personne.field.debut_visio',
                'required' => false,
                'input_format' => 'H:i',
                'widget' => 'text',
                'placeholder' => '00',
                'attr' => [
                  'class' => 'fr-input fr-fieldset-autosave',
                  'style' => 'text-align:center;'
                ]
           ])
           ->add('finVisio', TimeType::class, [
              'label' => 'affaire_personne.field.fin_visio',
              'required' => false,
              'input_format' => 'H:i',
              'widget' => 'text',
              'placeholder' => '00',
              'attr' => [
                'class' => 'fr-input fr-fieldset-autosave',
                'style' => 'text-align:center;'
              ]
           ])
            ->add('isDefere',  CheckboxType::class,[
                'disabled' => false,
                'label' => 'affaire_personne.field.is_defere',
                'required' => false,
            ])
            ->add('dateDeferement',  DateType::class, [
                // 'format' => 'dd/MM/yyyy',
                'widget' => 'single_text',
                'html5' => true,
                'label' => false,
                'attr' => [
                  'class' => 'fr-input fr-fieldset-autosave',
                  'placeholder' => 'personne.field.date_deces',
                ],
                'required' => false,
            ])
            ->add('dateConvocation',  DateType::class, [
                'label' => 'affaire_personne.field.date_convocation',
                // 'format' => 'dd/MM/yyyy',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                  'class' => 'fr-input fr-fieldset-autosave',
                  'placeholder' => 'personne.field.date_deces',
                ],
                'required' => false,
            ])
            ->add('avocat', TextType::class, [
                'label' => 'affaire_personne.field.avocat',
                'required' => false,
                'attr' => [
                  'class' => 'fr-input fr-fieldset-autosave autocomplete-avocat',
                ]
            ])
            ->add('affaireNatinfs', CollectionType::class, [
              'entry_type'    => AffaireNatinfReadOnlyType::class,
              //'mapped'        => false,
            ])
            ->add('changeEtat', RichCheckboxType::class,[
              'mapped' => false,
              'label' => 'affaire_personne.field.statut',
              'required' => false,
            ])
            ->add('statut', EntityType::class, [
              'label' => 'affaire_personne.field.statut',
              'choice_label' => 'libelle',
              'disabled' => true,
              'class' => StatutPersonne::class,
              'query_builder' => function (StatutPersonneRepository $er): QueryBuilder {
                return $er->createQueryBuilder('u')
                    ->where('u.code IN(:codes)')
                    ->setParameter('codes', [
                      StatutPersonne::CODE_VICTIME,
                      StatutPersonne::CODE_PARTIE_CIVILE,
                      StatutPersonne::CODE_PARTIE_CIVILE_RL,
                      StatutPersonne::CODE_PARTIE_CIVILE_ABUSIVE,
                      StatutPersonne::CODE_PARTIE_CIVILE_OPPOSANT,
                    ])
                    ->orderBy('u.libelle', 'DESC');
               },
               'attr' => [
                 'class' => 'fr-select update_statut',
               ],
               'required' => true,
            ])
            ->add('modePoursuite', EntityType::class, [
              'label' => 'affaire_personne.field.mode_poursuite',
              'choice_label' => 'libelleLong',
              'class' => ModePoursuite::class,
              'query_builder' => function (ModePoursuiteRepository $er): QueryBuilder {
                return $er->createQueryBuilder('u')
                    ->orderBy('u.libelle', 'ASC');
               },
               'attr' => [
                 'class' => 'fr-select fr-fieldset-autosave',
               ],
               'required' => false,
            ])
            ->add('natureJugement', ChoiceType::class, [
              'label' => 'affaire_personne.field.nature_jugement',
              'choices' => [
                'C' => 'C',
                'CAS' => 'CAS',
                'D' => 'D',
                'ID' => 'ID',
              ],
              'expanded' => true,
              'multiple' => false,
              'required' => false,
              'attr' => [
                'class' => '',
              ]
            ])
            ->add('modeComparution', EntityType::class, [
              'label' => 'affaire_personne.field.mode_comparution',
              'choice_label' => 'libelleCourt',
              'class' => ModeComparution::class,
              'query_builder' => function (ModeComparutionRepository $er): QueryBuilder {
                return $er->createQueryBuilder('u')
                    ->orderBy('u.libelle', 'ASC');
               },
               'attr' => [
                 'class' => 'fr-select fr-fieldset-autosave',
               ],
               'required' => false,
            ])
            ->add('assisteDe', TextType::class, [
              'label' => 'affaire_personne.field.assiste_de',
              'attr' => [
                'class' => 'fr-input fr-fieldset-autosave autocomplete-avocat',
              ],
              'required' => false,
            ])
            ->add('modeConvocation', EntityType::class, [
              'label' => 'affaire_personne.field.mode_convocation',
              'choice_label' => 'libelleCourt',
              'class' => ModeConvocation::class,
              'query_builder' => function (ModeConvocationRepository $er): QueryBuilder {
                return $er->createQueryBuilder('u')
                    ->orderBy('u.libelle', 'ASC');
               },
               'attr' => [
                 'class' => 'fr-select fr-fieldset-autosave',
               ],
               'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AffairePersonne::class,
        ]);
    }
}
