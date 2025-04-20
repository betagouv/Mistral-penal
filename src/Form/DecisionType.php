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

use App\Entity\Affaire;
use App\Entity\AffaireNatinf;
use App\Entity\AffairePersonne;
use App\Entity\Decision;
use App\Entity\DecisionPrevention;
use App\Entity\DecisionSanction;
use App\Entity\ModulationPeine;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class DecisionType extends AbstractType
{
    public function __construct(private LoggerInterface $logger) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
      $affaire = $options['affaire'];
      $builder
            ->add('id', HiddenType::class, [
              'mapped' => false 
            ])
            ->add('affaire', EntityType::class, [
              'block_prefix' => '_affaire_hidden',
              'choice_label' => 'id',
              'label' => false,
              'choices' => [$affaire],
              'class' => Affaire::class,
              'data' => $options['affaire'],
              'attr' => [
                'class' => 'fr-select',
              ]
            ])
            ->add('affairePersonne', EntityType::class, [
              'block_prefix' => '_affaire_hidden',
              'choice_label' => 'id',
              'choices' => $affaire->getAffairePersonnes(),
              'label' => false,
              'class' => AffairePersonne::class,
              'attr' => [
                'class' => 'fr-select',
              ]
            ])
            ->add('decisionPrevention', EntityType::class, [
              'choice_label' => 'libelle',
              'label' => 'decision.field.decisionPrevention.title',
              'class' => DecisionPrevention::class,
              'placeholder' => 'decision.field.decisionPrevention.placeholder',
              'query_builder' => function (EntityRepository $er) : QueryBuilder {
                  return $er->createQueryBuilder('u')
                            ->orderBy('u.id', 'ASC');
              },
              'attr' => [
                'class' => 'fr-select',
              ]
            ])
            ->add('decisionSanction', EntityType::class, [
              'choice_label' => 'libelle',
              'label' => 'decision.field.decisionSanction.title',
              'class' => DecisionSanction::class,
              'placeholder' => 'decision.field.decisionPrevention.placeholder',
              'query_builder' => function (EntityRepository $er) : QueryBuilder {
                  return $er->createQueryBuilder('u')
                            ->orderBy('u.id', 'ASC');
              },
              'attr' => [
                'class' => 'fr-select',
              ],
              'required' => false,
            ])
            ->add('modulationPeine', EntityType::class, [
              'choice_label' => 'libelle',
              'label' => 'decision.field.modulationPeine.title',
              'class' => ModulationPeine::class,
              'placeholder' => 'decision.field.decisionPrevention.placeholder',
              'query_builder' => function (EntityRepository $er) : QueryBuilder {
                  return $er->createQueryBuilder('u')
                            ->orderBy('u.id', 'ASC');
              },
              'attr' => [
                'class' => 'fr-select',
              ],
              'required' => false,
            ])
            ->add('peines', TextareaType::class, [
              'label' => 'decision.field.peines.title',
              'attr' => [
                'class' => 'fr-input',
                'spellcheck' => 'false',
                'placeholder' => 'decision.field.peines.placeholder',
                'style' => 'height:86px'
              ],
              'required' => false,
            ])
          ->add('numeroMinute', TextType::class, [
              'label' => 'decision.field.numeroMinute.title',
              'attr' => [
                  'class' => 'fr-input',
                  'placeholder' => 'decision.field.numeroMinute.placeholder',
                  'spellcheck' => 'false',
                  'pattern' => '\d{4}/\d+',
                  'autocomplete' => 'off',
              ],
              'help' => 'decision.field.numeroMinute.help',
              'required' => false,
          ])
            ->add('submit', SubmitType::class, [
              'label' => 'Enregistrer',
              'attr' => [
                'class' => 'fr-btn'
              ]
            ])
            ;
            

            $affaireNatinfConfig = [
                'label' => 'decision.field.affaireNatinfs.title',
                'block_prefix' => '_dsfr_checkbox',
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'choice_label'  => function (AffaireNatinf $affaireNatinf) {
                    return '<span class="natinf-code">'.$affaireNatinf->getNatinf()->getCode().'</span>'
                        .' <div class="natinf-libelle fr-ml-2w">'.$affaireNatinf->getNatinf()->getLibelle().' </div>';
                },
                'class' => AffaireNatinf::class
            ];

            if ($options["affaireNatinfsFromAffaire"]) {
                $choices = [];
                foreach ($affaire->getAffaireNatinfs() as $affaireNatinf) {
                    foreach ($affaireNatinf->getPersonnes() as $personne) {
                        if (!$personne->isDisqualifie()) {
                            $choices[] = $affaireNatinf;
                        }
                    }
                }

                $affaireNatinfConfig["choices"] = $choices;
            } else {
                $affaireNatinfConfig["query_builder"] = function (EntityRepository $er)use ($affaire) : QueryBuilder   {
                    return $er->createQueryBuilder('u')
                        ->join('u.natinf', 'n')
                        ->leftJoin('u.personnes', 'np')
                        ->andWhere('u.affaire = :affaire')
                        ->andWhere('np.isDisqualifie = false')
                        ->setParameter('affaire', $affaire)
                        ->addOrderBy('u.duplicateRoot', 'ASC')
                        ->addOrderBy('u.id', 'ASC')
                    ;
                };
            }

            $builder->add('affaireNatinfs', EntityType::class, $affaireNatinfConfig);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Decision::class,
            'affaireNatinfsFromAffaire' => false
        ]);
        $resolver->setRequired('affaire');
    }
}
