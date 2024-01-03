<?php

namespace Drupal\ctarpr\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure CTARPR settings for this site.
 */
class CTARPRSettingsForm extends ConfigFormBase
{

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an Example object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public static function create(ContainerInterface $container)
  {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ctarpr_c_t_a_r_p_r_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ctarpr.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('ctarpr.settings');
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $user_roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();


    $form['label'] = ['#markup' => '<strong>'.t('Please select user roles for restricting content type').'</strong>'];
    $form['space'] = ['#markup' => '<hr><br>'];
    foreach ($content_types as $content_type)
    {
      $form['content_type_'.$content_type->id()] =
      [
        '#type' => 'label',
        '#title' => '<br>'.$content_type->label(),
      ];

      $user_roles_indexed_array = [];
      foreach ($user_roles as $user_role)
      {
        $user_roles_indexed_array[] = $user_role->label();
      }
        $form['restricted_user_roles_'.$content_type->id()] =
        [
          '#type' => 'checkboxes',
          '#options' => $user_roles_indexed_array,
          '#default_value' => $config->get('restricted_user_roles_'.$content_type->id()) ?? [],
        ];

        $form['page_redirection_'.$content_type->id()] =
          [
            '#title' =>  $this->t('Link for redirection'),
            '#description' => $this->t('Please type just number of node. For example "14" without quotes<hr>'),
            '#type' => 'number',
            '#default_value' => $config->get('page_redirection_'.$content_type->id()),
            '#markup' => '<hr>'
          ];
      }
    return parent::buildForm($form, $form_state);
  }
  

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($content_types as $content_type)
    {
      $this->config('ctarpr.settings')
        ->set('content_type_'.$content_type->id(), $form_state->getValue('content_type_'.$content_type->id()))
        ->set('page_redirection_'.$content_type->id(), $form_state->getValue('page_redirection_'.$content_type->id()));
      $restricted_roles = $form_state->getValue('restricted_user_roles_'.$content_type->id());
      $restricted_roles = array_values($restricted_roles);
      foreach (array_keys($restricted_roles, 0, true) as $key) {
        unset($restricted_roles[$key]);
      }

      $this->config('ctarpr.settings')->set('restricted_user_roles_'.$content_type->id(), $restricted_roles);
      $this->config('ctarpr.settings')->save();
    }
    parent::submitForm($form, $form_state);
  }
}
