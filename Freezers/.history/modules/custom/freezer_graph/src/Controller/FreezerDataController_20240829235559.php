<?php   

namespace Drupal\freezer_graph\Controller;

use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\File\FileSystemInterface;
class FreezerDataController extends ControllerBase
{
    /**
     * Receives data from the request, checks if the value is below the threshold, and sends an alert email if it is.
     */
    public function receiveData(Request $request)
    {
        $dataValues = [
            'freezerOne' => [
                'value' => $request->request->get('data1'),
                'name' => $request->request->get('data2'),
            ],
            'freezerTwo' => [
                'value' => $request->request->get('data3'),
                'name' => $request->request->get('data4'),
            ],
            // 'freezerThree' => [
            //     'value' => $request->request->get('data5'),
            //     'name' => $request->request->get('data6'),
            // ],
        ];
    
        $current_time = \Drupal::time()->getRequestTime();
        $formatted_time = date('Y-m-d H:i:s', $current_time);
    
        $responses = [];
    
        foreach ($dataValues as $key => $data) {
            $term = $this->loadOrCreateTerm($data['name']);
            $node_data = $this->createOrUpdateNode($data, $term, $formatted_time);
    
            //!CHECKING THRESHOLD VALUES AFTER CREATING/UPDATING FREEZER_NAMES CONTENT TYPE
            $thresholds = $this->getThreshold($data['name']);
    
            if ($thresholds !== NULL) {
                if ($data['value'] > $thresholds['maximum_threshold']) {
                    \Drupal::messenger()->addMessage('Temperature is high: ' . $data['value'], MessengerInterface::TYPE_WARNING);
                    $temperature_status = 'high';
                    $this->handleFreezerAlert($data['name'], $data['value'], $temperature_status);

                } elseif ($data['value'] < $thresholds['set_temperature']) {
                    \Drupal::messenger()->addMessage('Temperature is low: ' . $data['value'], MessengerInterface::TYPE_WARNING);
                    $temperature_status = 'low';
                    $this->handleFreezerAlert($data['name'], $data['value'], $temperature_status);
                } else {
                    \Drupal::messenger()->addMessage('Temperature is within the acceptable range: ' . $data['value'], MessengerInterface::TYPE_STATUS);
                    $temperature_status = 'normal';
                   // $this->handleFreezerAlert($data['name'], $data['value'], $temperature_status);
                }
            } else {
                \Drupal::messenger()->addMessage('Threshold values not found for: ' . $data['name'], MessengerInterface::TYPE_ERROR);
                $temperature_status = 'thresholds_not_found';
            }
    
            $responses[$key] = [
                'node_id' => $node_data['node_id'],
                'status' => 'success',
                'freezerName'=> $data['name'],
                'message' => 'Data saved successfully',
                'temperature' => $data['value'],
                'time' => $formatted_time,
                'temperature_status' => $temperature_status,
            ];
        }
    


         // Construct the log entry as a single line
         $log_entry_keys = [];
         $log_entry_values = [];
         foreach ($responses as $key => $response) {
             $log_entry_keys[] = sprintf("%s(%s)", $response['freezerName'], $response['time']);
             $log_entry_values[] = sprintf(
                 '"freezerName":"%s","time":"%s","status":"%s","temperature":"%s","temperature_status":"%s","node_id":"%s","message":"%s"',
                 $response['freezerName'],
                 $response['time'],
                 $response['status'],
                 $response['temperature'],
                 $response['temperature_status'],
                 $response['node_id'],
                 $response['message']
             );
         }
         $log_entry = 'MicroController[' . implode(',', $log_entry_keys) . '] : [' . implode(',', $log_entry_values) . ']';
 
 
         // Write the log entry to a file
         $log_file_path = 'private://freezer_responses_log.log';
         //$log_file_path = '/var/log/apache2/access.log';
 
         $file_system = \Drupal::service('file_system');
         $real_log_file_path = $file_system->realpath($log_file_path);
         $directory_path = dirname($real_log_file_path);
 
         // Ensure the directory exists
         if ($file_system->prepareDirectory($directory_path, FileSystemInterface::CREATE_DIRECTORY)) {
             if (file_put_contents($real_log_file_path, $log_entry . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
                 \Drupal::logger('freezer_graph')->error('Failed to write to log file: @path', ['@path' => $real_log_file_path]);
             } else {
                 \Drupal::logger('freezer_graph')->debug('Log entry written to file: @path', ['@path' => $real_log_file_path]);
             }
         } else {
             \Drupal::logger('freezer_graph')->error('Failed to prepare directory: @path', ['@path' => $directory_path]);
         }
 
 



        return new JsonResponse($responses);
    }
    
    /**
     * Loads or creates a taxonomy term for the given name.
     */
    private function loadOrCreateTerm($name)
    {
        // Check if the value is already present in the taxonomy.
        $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
        $terms = $term_storage->loadByProperties(['name' => $name]);

        if (empty($terms)) {
            // Get all terms in the vocabulary to find the highest weight.
            $all_terms = $term_storage->loadByProperties(['vid' => 'freezer_name_list']);
            $max_weight = -1;
            
            // Iterate through all terms to find the maximum weight.
            foreach ($all_terms as $existing_term) {
                $weight = $existing_term->get('weight')->value;
                if ($weight > $max_weight) {
                    $max_weight = $weight;
                }
            }

            // Increment the maximum weight by 1 for the new term.
            $new_weight = $max_weight + 1;

            // Value is not present, add it to the taxonomy with the incremented weight.
            $term = \Drupal\taxonomy\Entity\Term::create([
                'vid' => 'freezer_name_list', // Replace 'freezer_name_list' with your vocabulary machine name.
                'name' => $name,
                'weight' => $new_weight,
            ]);
            $term->save();
        } else {
            // If the term exists, load the first matching term.
            $term = reset($terms);
        }
        
        return $term;
    }

    /**
     * Creates or updates a node with the given data.
     */
    // private function createOrUpdateNode($data, $term, $formatted_time)
    // {
    //     $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    //     $existing_node_query = $node_storage->getQuery()
    //         ->condition('type', 'freezer_names')
    //         ->condition('title', $data['name'])
    //         ->range(0, 1)
    //         ->accessCheck(FALSE);

    //     $existing_nodes = $existing_node_query->execute();

    //     $nodeData = \Drupal\node\Entity\Node::create([
    //         'type' => 'freezer_data',
    //         'title' => $data['name'] . ' Data Received on ' . $formatted_time,
    //         'field_value' => $data['value'],
    //         'field_time' => $formatted_time,
    //         'field_f_names' => $data['name'],
    //         'field_freezer_names' => $term->id(),
    //     ]);
    //     $nodeData->save();

    //     if (empty($existing_nodes)) {
    //         $node = \Drupal\node\Entity\Node::create([
    //             'type' => 'freezer_names',
    //             'title' => $data['name'],
    //             'field_set_temperature' => -80,                    //    Min Temp Required if below this send email
    //             'field_maximum_threshold' => -70,                  //    Max Temp  Required if above this send email
    //         ]);
    //     } else {
    //         $existing_node_id = reset($existing_nodes);
    //         $node = \Drupal\node\Entity\Node::load($existing_node_id);
    //     }

    //     $existing_references = $node->get('field_freezer_data')->referencedEntities();
    //     $existing_references[] = $nodeData;
    //     $node->set('field_freezer_data', $existing_references);
    //     $node->save();

    //     return [
    //         'node_id' => $nodeData->id(),
    //     ];
    // }





    private function createOrUpdateNode($data, $term, $formatted_time)
    {
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $existing_node_query = $node_storage->getQuery()
            ->condition('type', 'freezer_names')
            ->condition('title', $data['name'])
            ->range(0, 1)
            ->accessCheck(FALSE);
    
        $existing_nodes = $existing_node_query->execute();
    
        // Create or update the 'freezer_names' node
        if (empty($existing_nodes)) {
            $node = \Drupal\node\Entity\Node::create([
                'type' => 'freezer_names',
                'title' => $data['name'],
                'field_set_temperature' => -80,                    // Min Temp Required if below this send email
                'field_maximum_threshold' => -70,                  // Max Temp  Required if above this send email
                'field_current_value' => $data['value'],           // Initial value
                'field_current_time' => $formatted_time,           // Initial value
            ]);
        } else {
            $existing_node_id = reset($existing_nodes);
            $node = \Drupal\node\Entity\Node::load($existing_node_id);
    
            // Update the 'field_current_value' with the new value
            $node->set('field_current_value', $data['value']);
            $node->set('field_current_time', $formatted_time);
        }
    
        // Save the data to a CSV file
        $file_name = 'private://' . $data['name'] . '.csv';
        $file = fopen($file_name, 'a');
        fputcsv($file, [$data['value'], $formatted_time]);
        fclose($file);
    
        $node->save();
    
        return [
            'node_id' => $node->id(),
        ];
    }
    
    






    /**
     * Gets the threshold value for a freezer from the corresponding node.
     */
    private function getThreshold($name)
    {
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $existing_node_query = $node_storage->getQuery()
            ->condition('type', 'freezer_names')
            ->condition('title', $name)
            ->range(0, 1)
            ->accessCheck(FALSE);

        $existing_nodes = $existing_node_query->execute();

        if (!empty($existing_nodes)) {
            $existing_node_id = reset($existing_nodes);
            $node = \Drupal\node\Entity\Node::load($existing_node_id);

            $thresholds = [];

            if ($node->hasField('field_set_temperature') && !$node->get('field_set_temperature')->isEmpty()) {
                $thresholds['set_temperature'] = (float) $node->get('field_set_temperature')->value;
            }

            if ($node->hasField('field_maximum_threshold') && !$node->get('field_maximum_threshold')->isEmpty()) {
                $thresholds['maximum_threshold'] = (float) $node->get('field_maximum_threshold')->value;
            }

            return $thresholds;
        }
        return NULL;
    }

    /**
     * Handles sending alert emails based on freezer value.
     */
    private function handleFreezerAlert($name, $value, $temperature_status)
    {
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        \Drupal::messenger()->addMessage("Loaded node storage.");
    
        $existing_node_query = $node_storage->getQuery()
            ->condition('type', 'freezer_names')
            ->condition('title', $name)
            ->range(0, 1)
            ->accessCheck(FALSE);
        \Drupal::messenger()->addMessage("Created node query.");
    
        $existing_nodes = $existing_node_query->execute();
        \Drupal::messenger()->addMessage("Executed node query.");
    
        if (!empty($existing_nodes)) {
            $existing_node_id = reset($existing_nodes);
            $node = \Drupal\node\Entity\Node::load($existing_node_id);
            \Drupal::messenger()->addMessage("Loaded node with ID: " . $existing_node_id);
    
            // Get CC and From emails
            $cc_emails = $this->getDefaultCcEmails();
            $from_email = $this->getDefaultFromEmail();
            if ($node->hasField('field_faculties')) {
                \Drupal::messenger()->addMessage("Node has field 'field_faculties'.");
                \Drupal::logger('freezer_graph')->debug("Node has field 'field_faculties'.");
    
                $faculty_field = $node->get('field_faculties');
                \Drupal::messenger()->addMessage("Got 'field_faculties' field.");
                \Drupal::logger('freezer_graph')->debug("Got 'field_faculties' field: " . json_encode($faculty_field->getValue()));
    
                if (!$faculty_field->isEmpty()) {
                    $faculty_ids = $faculty_field->getValue();
                    \Drupal::messenger()->addMessage("Got faculty IDs.");
                    \Drupal::logger('freezer_graph')->debug("Got faculty IDs: " . implode(', ', array_column($faculty_ids, 'target_id')));
    
                    $user_emails = $this->getUserEmailsFromIds($faculty_ids);
                    \Drupal::messenger()->addMessage("Got user emails.");
                    \Drupal::logger('freezer_graph')->debug("Got user emails: " . implode(', ', $user_emails));
    
                    $user_mobiles = $this->getUserMobileNumbersFromIds($faculty_ids);
                    \Drupal::messenger()->addMessage("Got user mobile numbers.");
                    \Drupal::logger('freezer_graph')->debug("Got user mobile numbers: " . implode(', ', $user_mobiles));
    

                    if (!empty($user_emails)) {
                        \Drupal::messenger()->addMessage("Sending alert email.");
                        \Drupal::logger('freezer_graph')->debug("Sending alert email to: " . implode(', ', $user_emails));
                        $this->sendAlertEmail($name, $value, $temperature_status, $user_emails, $cc_emails, $from_email, true);
                    }
    
                    //! Retrieve default mobile numbers and merge them

                    // Retrieve the list of default mobile numbers
                    $default_mobiles = $this->getAllMobileNumbers();

                    // Retrieve the list of user-specific mobile numbers
                    // Assuming $user_mobiles is already defined elsewhere in your code

                    // Sending alert SMS to all user-specific mobile numbers
                    if (!empty($user_mobiles)) {
                        \Drupal::messenger()->addMessage("Sending alert SMS to user-specific numbers.");
                        \Drupal::logger('freezer_graph')->emergency("Sending alert SMS to user-specific numbers: " . implode(', ', $user_mobiles));
                        foreach ($user_mobiles as $mobile_number) {
                            $this->sendAlertSMS($name, $value, $temperature_status, [$mobile_number], true);
                        }
                    }

                    // Sending alert SMS to all default mobile numbers
                    if (!empty($default_mobiles)) {
                        \Drupal::messenger()->addMessage("Sending alert SMS to default numbers.");
                        \Drupal::logger('freezer_graph')->emergency("Sending alert SMS to default numbers: " . implode(', ', $default_mobiles));
                        foreach ($default_mobiles as $mobile_number) {
                            $this->sendAlertSMS($name, $value, $temperature_status, [$mobile_number], false);
                        }
                    }




    
                    return;
                }
            }
        }
    
        // If no faculty emails or mobiles are found, send email to default address
        $default_email = $this->getDefaultEmail();
        $cc_emails = $this->getDefaultCcEmails();
        $from_email = $this->getDefaultFromEmail();
        $this->sendAlertEmail($name, $value, $temperature_status, [$default_email], $cc_emails, $from_email, false);
    
        // Retrieve default mobile numbers
        $user_mobiles = $this->getAllMobileNumbers();
        // Sending alert SMS to all retrieved mobile numbers
        foreach ($user_mobiles as $mobile_number) {
            $this->sendAlertSMS($name, $value, $temperature_status, [$mobile_number], false);
        }
    }
    




//! try

private function sendAlertSMS($name, $value, $temperature_status, $mobile_numbers, $applySkipping)
{
    \Drupal::logger('freezer_graph')->error('AA.');
    $url = 'https://adminapis.backendprod.com/lms_campaign/api/whatsapp/template/3qre8m5z1u/process';
    
    foreach ($mobile_numbers as $receiver) {
        $timestamp = \Drupal::time()->getRequestTime();
        $formatted_timestamp = date('Y-m-d H:i:s', $timestamp);
        $data = [
            'receiver' => $receiver,
            'values' => [
                '1' => $name,
                '2' => $formatted_timestamp,
                '3' => $value,
                // '4' => $temperature_status,
            ]
        ];
    
        $json_data = json_encode($data);
    
        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => $json_data,
            ],
        ];
    
        $context  = stream_context_create($options);
        $timestamp = \Drupal::time()->getRequestTime();
    
        // Get the state service to store SMS sending history
        $state = \Drupal::service('state');
        $smsHistory = $state->get('freezer_graph.sms_history', []);
    
        // Initialize SMS history for the receiver and freezer name if not set
        if (!isset($smsHistory[$receiver][$name])) {
            $smsHistory[$receiver][$name] = [];
        }
    
        // Filter history to keep only entries within the last 30 minutes
        $smsHistory[$receiver][$name] = array_filter($smsHistory[$receiver][$name], function ($time) use ($timestamp) {
            return ($timestamp - $time) <= 1800;
        });
    
        // Debug: Log and display SMS history
        \Drupal::logger('freezer_graph')->info('SMS history for @receiver and @name: @history', ['@receiver' => $receiver, '@name' => $name, '@history' => print_r($smsHistory[$receiver][$name], TRUE)]);
        \Drupal::messenger()->addMessage(t('SMS history for @receiver and @name: @history', ['@receiver' => $receiver, '@name' => $name, '@history' => print_r($smsHistory[$receiver][$name], TRUE)]));
    
        // Check if the SMS was sent in the last 5 minutes continuously
        if ($applySkipping && count($smsHistory[$receiver][$name]) >= 5) {
            $reason = 'Skipping SMS to prevent spamming';
            \Drupal::logger('freezer_graph')->notice($reason . ' for receiver ' . $receiver . ' and freezer ' . $name);
            \Drupal::messenger()->addMessage(t('@reason for receiver @receiver and freezer @name.', ['@reason' => $reason, '@receiver' => $receiver, '@name' => $name]), 'warning');
    
            // Log the skipped SMS
            $this->logEmail($receiver, $timestamp, 'skipped', $reason, "Freezer {$name} alert: Temperature {$temperature_status} at {$value}", $name, $value, 'whatsapp');
            continue;
        }
    
        $result = file_get_contents($url, false, $context);
    
        if ($result === FALSE) {
            \Drupal::logger('freezer_graph')->error('AA There was a problem sending the alert SMS to ' . $receiver);
            \Drupal::messenger()->addMessage('There was a problem sending the alert SMS to ' . $receiver, MessengerInterface::TYPE_ERROR);
            $this->logEmail($receiver, $timestamp, 'failed', 'There was a problem sending the alert SMS', "Freezer {$name} alert: Temperature {$temperature_status} at {$value}", $name, $value, 'WhatsApp');
        } else {
            \Drupal::logger('freezer_graph')->notice('AA Alert SMS sent successfully to ' . $receiver);
            \Drupal::messenger()->addMessage('Alert SMS sent successfully to ' . $receiver, MessengerInterface::TYPE_STATUS);
            $this->logEmail($receiver, $timestamp, 'sent', 'SMS Sent', "Freezer {$name} alert: Temperature {$temperature_status} at {$value}", $name, $value, 'whatsapp');
    
            // Add current timestamp to SMS history for the receiver and freezer name
            $smsHistory[$receiver][$name][] = $timestamp;
        }
    }
    
    // Save the updated SMS history back to the state
    $state->set('freezer_graph.sms_history', $smsHistory);
    
    // Debug: Log and display updated SMS history
    \Drupal::logger('freezer_graph')->info('Updated SMS history for @receiver and @name: @history', ['@receiver' => $receiver, '@name' => $name, '@history' => print_r($smsHistory[$receiver][$name], TRUE)]);
    \Drupal::messenger()->addMessage(t('Updated SMS history for @receiver and @name: @history', ['@receiver' => $receiver, '@name' => $name, '@history' => print_r($smsHistory[$receiver][$name], TRUE)]));
}














































































































    /**
     * Gets user emails from user IDs.
     */
    private function getUserEmailsFromIds($user_ids)
    {
        $emails = [];
        foreach ($user_ids as $user_id) {
            $user = \Drupal\user\Entity\User::load($user_id['target_id']);
            if ($user) {
                $emails[] = $user->getEmail();
            }
        }
        \Drupal::logger('freezer_graph')->debug("AAA:" . implode(',', $emails));
        return $emails;
    }

    /**
     * Gets user mobile numbers from user IDs.
     */
    private function getUserMobileNumbersFromIds($user_ids)
    {
        $mobile_numbers = [];
        foreach ($user_ids as $user_id) {
            $user = \Drupal\user\Entity\User::load($user_id['target_id']);
            if ($user && $user->hasField('field_mobile_number') && !$user->get('field_mobile_number')->isEmpty()) {
                $mobile_numbers[] = $user->get('field_mobile_number')->value;
            }
        }
        \Drupal::logger('freezer_graph')->debug("Mobile numbers: " . implode(',', $mobile_numbers));
        return $mobile_numbers;
    }

    private function sendAlertEmail($name, $value, $thresholdStatus, $emails, $ccEmails, $from_email, $applySkipping)
    {
        $mailManager = \Drupal::service('plugin.manager.mail');
        $module = 'freezer_graph'; // Replace with your module name.
        $key = 'alert_email'; // Ensure this matches the key in freezer_graph_mail
        $params = [
            'message' => "Dear Sir/Ma'am,\n\nPlease note that the value for '{$name}' is below the threshold.\nCurrent value: {$value}.\nThreshold status: {$thresholdStatus}.\n\nYou are advised to monitor the status of the freezer using www.ncbs.res.in/thermometry.\n\nRegards,\nInstrumentation Team",
            'title' => 'Alert: -80° Freezer Status',
            'from' => $from_email,
            'ccEmails' => (array) $ccEmails
        ];
    
        $langcode = \Drupal::currentUser()->getPreferredLangcode();
        $send = true;
    
        // Log the parameters to debug
        \Drupal::logger('freezer_graph')->info('Sending email with params: @params', ['@params' => print_r($params, TRUE)]);
        \Drupal::messenger()->addMessage(t('Sending email with params: @params', ['@params' => print_r($params, TRUE)]));
    
        // Get the state service to store email sending history
        $state = \Drupal::service('state');
        $timestamp = \Drupal::time()->getRequestTime();
        $emailHistory = $state->get('freezer_graph.email_history', []);
    
        // Initialize email history for the freezer name if not set
        if (!isset($emailHistory[$name])) {
            $emailHistory[$name] = [];
        }
    
        // Filter history to keep only entries within the last 30 minutes
        $emailHistory[$name] = array_filter($emailHistory[$name], function ($time) use ($timestamp) {
            return ($timestamp - $time) <= 1800;
        });
    
        // Debug: Log and display email history
        \Drupal::logger('freezer_graph')->info('Email history for @name: @history', ['@name' => $name, '@history' => print_r($emailHistory[$name], TRUE)]);
        \Drupal::messenger()->addMessage(t('Email history for @name: @history', ['@name' => $name, '@history' => print_r($emailHistory[$name], TRUE)]));
    
        // Check if the email was sent in the last 5 minutes continuously
        if ($applySkipping && count($emailHistory[$name]) >= 5) {
            $reason = 'Skipping email to prevent spamming';
            \Drupal::logger('freezer_graph')->notice($reason . ' for freezer ' . $name);
            \Drupal::messenger()->addMessage(t('@reason for freezer @name.', ['@reason' => $reason, '@name' => $name]), 'warning');
    
            // Log the skipped email for all recipients
            foreach ($emails as $to) {
                $this->logEmail($to, $timestamp, 'skipped', $reason, $params['message'], $name, $value, 'email');
            }
            return;
        }
    
        foreach ($emails as $to) {
            $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
            if ($result['result'] !== true) {
                $reason = 'There was a problem sending the alert email';
                \Drupal::logger('freezer_graph')->error($reason . ' to ' . $to);
                \Drupal::messenger()->addMessage(t('@reason to @to', ['@reason' => $reason, '@to' => $to]), 'error');
                $this->logEmail($to, $timestamp, 'failed', $reason, $params['message'], $name, $value, 'email');
            } else {
                \Drupal::logger('freezer_graph')->notice('Alert email sent successfully to ' . $to);
                \Drupal::messenger()->addMessage(t('Alert email sent successfully to @to', ['@to' => $to]), 'status');
    
                // Add current timestamp to email history for the freezer name
                $emailHistory[$name][] = $timestamp;
                // Log the sent email
                $this->logEmail($to, $timestamp, 'sent', 'Email Sent', $params['message'], $name, $value, 'email');
            }
        }
    
        // Save the updated email history back to the state
        $state->set('freezer_graph.email_history', $emailHistory);
    
        // Debug: Log and display updated email history
        \Drupal::logger('freezer_graph')->info('Updated email history for @name: @history', ['@name' => $name, '@history' => print_r($emailHistory[$name], TRUE)]);
        \Drupal::messenger()->addMessage(t('Updated email history for @name: @history', ['@name' => $name, '@history' => print_r($emailHistory[$name], TRUE)]));
    }


    
    /**
     * Gets the default email address from the 'set_default_email_id' content type.
     */
    private function getDefaultEmail()
    {
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $default_email_query = $node_storage->getQuery()
            ->condition('type', 'set_default_email_id')
            ->condition('title', 'Default Emails')
            ->range(0, 1)
            ->accessCheck(FALSE);

        $default_email_nodes = $default_email_query->execute();

        if (!empty($default_email_nodes)) {
            $default_email_node_id = reset($default_email_nodes);
            $node = \Drupal\node\Entity\Node::load($default_email_node_id);

            if ($node->hasField('field_set_default_email_id') && !$node->get('field_set_default_email_id')->isEmpty()) {
                return $node->get('field_set_default_email_id')->value;
            }
        }

        // Fallback to a static default email if none found
        return 'sahilst@ext.ncbs.res.in';
    }

    /**
     * Gets the default CC email addresses from the 'set_default_email_id' content type.
     */
    private function getDefaultCcEmails()
    {
        $ccEmails = [];
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $default_email_query = $node_storage->getQuery()
            ->condition('type', 'set_default_email_id')
            ->condition('title', 'Default Emails')
            ->range(0, 1)
            ->accessCheck(FALSE);

        $default_email_nodes = $default_email_query->execute();

        if (!empty($default_email_nodes)) {
            $default_email_node_id = reset($default_email_nodes);
            $node = \Drupal\node\Entity\Node::load($default_email_node_id);

            if ($node->hasField('field_set_emails_to_cc') && !$node->get('field_set_emails_to_cc')->isEmpty()) {
                foreach ($node->get('field_set_emails_to_cc') as $cc_email) {
                    $ccEmails[] = $cc_email->value;
                }
            }
        }

        // Fallback to static CC emails if none found
        if (empty($ccEmails)) {
            //$ccEmails = ['sahiltari36@gmail.com', 'sahiltari007@gmail.com', 'garrysnake47@gmail.com'];
            $ccEmails = [];
        }

        return $ccEmails;
    }

    /**
     * Gets the default "from" email address from the 'set_default_email_id' content type.
     */
    private function getDefaultFromEmail()
    {
        \Drupal::messenger()->addMessage("pp");
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $default_email_query = $node_storage->getQuery()
            ->condition('type', 'set_default_email_id')
            ->condition('title', 'Default Emails')
            ->range(0, 1)
            ->accessCheck(FALSE);

        $default_email_nodes = $default_email_query->execute();

        if (!empty($default_email_nodes)) {
            $default_email_node_id = reset($default_email_nodes);
            $node = \Drupal\node\Entity\Node::load($default_email_node_id);

            if ($node->hasField('field_set_from_email_id') && !$node->get('field_set_from_email_id')->isEmpty()) {
                return $node->get('field_set_from_email_id')->value;
            }
        }

        // Fallback to a static "from" email if none found
        return 'noreply@ncbs.res.in';
    }

    /**
     * Logs email details in the Email Log content type.
     */
    private function logEmail($email, $timestamp, $status, $reason, $message, $name, $value, $type)
    {
        $node = Node::create([
            'type' => 'logs',
            'title' => t('@type Log: @status - @timestamp', ['@type' => $type, '@status' => $status, '@timestamp' => date('Y-m-d H:i:s', $timestamp)]),
            'field_email_number' => $email,
            'field_timestamp_' => date('Y-m-d\TH:i:s', $timestamp),
            'field_status_' => $status,
            'field_reason_' => $reason,
            'field_message_' => $message,
            'field_freezer_name_log_' => $name,
            'field_current_temperature_' => $value,
            'field_type' => $type,
        ]);
        $node->save();
    }
    
    private function getAllMobileNumbers() {
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        \Drupal::logger('freezer_graph')->debug('Started getAllMobileNumbers function.');
        \Drupal::messenger()->addMessage('Started getAllMobileNumbers function.');
    
        $default_mobile_query = $node_storage->getQuery()
            ->condition('type', 'set_default_email_id')
            ->condition('title', 'Default Emails')
            ->range(0, 1)
            ->accessCheck(FALSE);
    
        $default_mobile_nodes = $default_mobile_query->execute();
        \Drupal::logger('freezer_graph')->debug('Executed default mobile query.');
        \Drupal::messenger()->addMessage('Executed default mobile query.');
    
        $mobile_numbers = [];
    
        if (!empty($default_mobile_nodes)) {
            $default_mobile_node_id = reset($default_mobile_nodes);
            \Drupal::logger('freezer_graph')->debug('Found default mobile node ID: ' . $default_mobile_node_id);
            \Drupal::messenger()->addMessage('Found default mobile node ID: ' . $default_mobile_node_id);
    
            $node = \Drupal\node\Entity\Node::load($default_mobile_node_id);
            \Drupal::logger('freezer_graph')->debug('Loaded default mobile node.');
            \Drupal::messenger()->addMessage('Loaded default mobile node.');
    
            if ($node->hasField('field_default_mobile_number') && !$node->get('field_default_mobile_number')->isEmpty()) {
                foreach ($node->get('field_default_mobile_number') as $item) {
                    $mobile_numbers[] = $item->value;
                    \Drupal::logger('freezer_graph')->debug('Found mobile number: ' . $item->value);
                    \Drupal::messenger()->addMessage('Found mobile number: ' . $item->value);
                }
            } else {
                \Drupal::logger('freezer_graph')->debug('Field field_default_mobile_number is empty or does not exist.');
                \Drupal::messenger()->addMessage('Field field_default_mobile_number is empty or does not exist.');
            }
        } else {
            \Drupal::logger('freezer_graph')->debug('No default mobile nodes found.');
            \Drupal::messenger()->addMessage('No default mobile nodes found.');
        }
    
        if (empty($mobile_numbers)) {
            // Fallback to a static default mobile number if none found
            \Drupal::logger('freezer_graph')->debug('Returning fallback default mobile number: 0000000000');
            \Drupal::messenger()->addMessage('Returning fallback default mobile number: 0000000000');
            $mobile_numbers[] = '';
        }
    
        return $mobile_numbers;
    }
    
    
}
