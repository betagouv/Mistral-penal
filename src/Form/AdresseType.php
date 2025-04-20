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

use App\Entity\Adresse;
use App\Entity\Pays;
use App\Repository\PaysRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdresseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
          ->add('ligne1', TextType::class, [
            'label' => 'adresse.field.ligne1',
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
            ],
            'required' => false,
          ])
          ->add('ligne2', TextType::class, [
            'label' => 'adresse.field.ligne2',
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
            ],
            'required' => false,
          ])
          ->add('ligne3', TextType::class, [
            'label' => 'adresse.field.ligne3',
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
            ],
            'required' => false,
          ])
          ->add('codePostal', TextType::class, [
            'label' => 'adresse.field.codePostal',
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
            ],
            'required' => false,
          ])
          ->add('localite', TextType::class, [
            'label' => 'adresse.field.localite',
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
            ],
            'required' => false,
          ])
          ->add('pays', EntityType::class, [
            'choice_label' => 'libelleCourt',
            'label' => 'adresse.field.pays',
            'class' => Pays::class,
            'query_builder' => function (PaysRepository $er): QueryBuilder {
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
            'data_class' => Adresse::class,
        ]);
    }
}
