<?php

/*
 * This file is part of RaspiSMS.
 *
 * (c) Pierre-Lin Bonnemaison <plebwebsas@gmail.com>
 *
 * This source file is subject to the GPL-3.0 license that is bundled
 * with this source code in the file LICENSE.
 */

namespace adapters;

    /**
     * Interface for phones adapters
     * Phone's adapters allow RaspiSMS to use a platform to communicate with a phone number.
     * Its an adapter between internal and external code, as an API, command line software, physical modem, etc.
     *
     * All Phone Adapters must implement this interface
     */
    class GammuAdapter implements AdapterInterface
    {
        /**
         * Datas used to configure interaction with the implemented service. (e.g : Api credentials, ports numbers, etc.).
         */
        private $datas;

        /**
         * Adapter constructor, called when instanciated by RaspiSMS.
         *
         * @param json string $datas  : JSON string of the datas to configure interaction with the implemented service
         */
        public function __construct(string $datas)
        {
            $this->datas = json_decode($datas, true);
        }

        /**
         * Classname of the adapter.
         */
        public static function meta_classname(): string
        {
            return __CLASS__;
        }
        
        /**
         * Uniq name of the adapter
         * It should be the classname of the adapter un snakecase
         */
        public static function meta_uid() : string
        {
            return 'gammu_adapter';
        }

        /**
         * Name of the adapter.
         * It should probably be the name of the service it adapt (e.g : Gammu SMSD, OVH SMS, SIM800L, etc.).
         */
        public static function meta_name(): string
        {
            return 'Gammu';
        }

        /**
         * Description of the adapter.
         * A short description of the service the adapter implements.
         */
        public static function meta_description(): string
        {
            return 'Utilisation du logiciel Gammu qui doit être installé sur le serveur et configuré. Voir https://wammu.eu.';
        }

        /**
         * List of entries we want in datas for the adapter.
         *
         * @return array : Every line is a field as an array with keys : name, title, description, required
         */
        public static function meta_datas_fields(): array
        {
            return [
                [
                    'name' => 'config_file',
                    'title' => 'Fichier de configuration',
                    'description' => 'Chemin vers le fichier de configuration que Gammu devra utilisé pour se connecter au téléphone.',
                    'required' => true,
                ],
                [
                    'name' => 'pin',
                    'title' => 'Code PIN',
                    'description' => 'Code PIN devant être utilisé pour activer la carte SIM (laisser vide pour ne pas utiliser de code PIN).',
                    'required' => false,
                ],
            ];
        }

        /**
         * Does the implemented service support reading smss.
         */
        public static function meta_support_read(): bool
        {
            return true;
        }

        /**
         * Does the implemented service support flash smss.
         */
        public static function meta_support_flash(): bool
        {
            return false;
        }

        /**
         * Does the implemented service support status change.
         */
        public static function meta_support_status_change(): bool
        {
            return false;
        }
        
        /**
         * Does the implemented service support reception callback.
         */
        public static function meta_support_reception(): bool
        {
            return false;
        }


        /**
         * Method called to send a SMS to a number.
         *
         * @param string $destination : Phone number to send the sms to
         * @param string $text        : Text of the SMS to send
         * @param bool   $flash       : Is the SMS a Flash SMS
         *
         * @return array : [
         *      bool 'error' => false if no error, true else
         *      ?string 'error_message' => null if no error, else error message
         *      ?string 'uid' => Uid of the sms created on success, null on error
         * ]
         */
        public function send(string $destination, string $text, bool $flash = false)
        {
            $response = [
                'error' => false,
                'error_message' => null,
                'uid' => null,
            ];

            if (!$this->unlock_sim())
            {
                $response['error'] = true;
                $response['error_message'] = 'Cannot unlock SIM.';
                return $response;
            }

            $command_parts = [
                'gammu',
                '--config',
                escapeshellarg($this->datas['config_file']),
                'sendsms',
                'TEXT',
                escapeshellarg($destination),
                '-text',
                escapeshellarg($text),
                '-validity',
                'MAX',
                '-autolen',
                mb_strlen($text),
            ];

            if ($flash)
            {
                $command_parts[] = '-flash';
            }

            $result = $this->exec_command($command_parts);
            if (0 !== $result['return'])
            {
                $response['error'] = true;
                $response['error_message'] = 'Gammu command failed.';
                return $response;
            }

            $find_ok = $this->search_for_string($result['output'], 'ok');
            if (!$find_ok)
            {
                $response['error'] = true;
                $response['error_message'] = 'Cannot find output OK.';
                return $response;
            }

            $uid = false;
            foreach ($result['output'] as $line)
            {
                $matches = [];
                preg_match('#reference=([0-9]+)#u', $line, $matches);

                if ($matches[1] ?? false)
                {
                    $uid = $matches[1];

                    break;
                }
            }

            if (false === $uid)
            {
                $response['error'] = true;
                $response['error_message'] = 'Cannot retrieve sms uid.';
                return $response;
            }

            $response['uid'] = $uid;
            return $response;
        }

        /**
         * Method called to read SMSs of the number.
         *
         * @return array : [
         *      bool 'error' => false if no error, true else
         *      ?string 'error_message' => null if no error, else error message
         *      array 'sms' => Array of the sms reads
         * ]
         */
        public function read(): array
        {
            $response = [
                'error' => false,
                'error_message' => null,
                'smss' => [],
            ];

            if (!$this->unlock_sim())
            {
                $response['error'] = true;
                $response['error_message'] = 'Cannot unlock sim.';
                return $response;
            }

            $command_parts = [
                PWD . '/bin/gammu_get_unread_sms.py',
                escapeshellarg($this->datas['config_file']),
            ];

            $return = $this->exec_command($command_parts);
            if (0 !== $return['return'])
            {
                $response['error'] = true;
                $response['error_message'] = 'Gammu command return failed.';
                return $response;
            }

            foreach ($return['output'] as $line)
            {
                $decode = json_decode($line, true);
                if (null === $decode)
                {
                    continue;
                }

                $response['smss'][] = [
                    'at' => $decode['at'],
                    'text' => $decode['text'],
                    'origin' => $decode['number'],
                ];
            }

            return $response;
        }

        /**
         * Method called to verify if the adapter is working correctly
         * should be use for exemple to verify that credentials and number are both valid.
         *
         * @return bool : False on error, true else
         */
        public function test(): bool
        {
            //Always return true as we cannot test because we would be needing a root account
            return true;
        }

        /**
         * Method called on reception of a status update notification for a SMS.
         *
         * @return mixed : False on error, else array ['uid' => uid of the sms, 'status' => New status of the sms (\models\Sended::STATUS_UNKNOWN, \models\Sended::STATUS_DELIVERED, \models\Sended::STATUS_FAILED)]
         */
        public static function status_change_callback()
        {
            return false;
        }

        /**
         * Function to unlock pin.
         *
         * @return bool : False on error, true else
         */
        private function unlock_sim(): bool
        {
            if (!$this->datas['pin'])
            {
                return true;
            }

            $command_parts = [
                'gammu',
                '--config',
                escapeshellarg($this->datas['config_file']),
                'entersecuritycode',
                'PIN',
                escapeshellarg($this->datas['pin']),
            ];

            $result = $this->exec_command($command_parts);

            //Check security status
            $command_parts = [
                'gammu',
                '--config',
                escapeshellarg($this->datas['config_file']),
                'getsecuritystatus',
            ];

            $result = $this->exec_command($command_parts);

            if (0 !== $result['return'])
            {
                return false;
            }

            return $this->search_for_string($result['output'], 'nothing');
        }

        /**
         * Function to execute a command and transmit it to Gammu.
         *
         * @param array $command_parts : Commands parts to be join with a space
         *
         * @return array : ['return' => int:return code of command, 'output' => array:each raw is a line of the output]
         */
        private function exec_command(array $command_parts): array
        {
            //Add redirect of error to stdout
            $command_parts[] = '2>&1';

            $command = implode(' ', $command_parts);

            $output = [];
            $return_var = null;
            exec($command, $output, $return_var);

            return ['return' => (int) $return_var, 'output' => $output];
        }

        /**
         * Function to search a string in the output of an executer command.
         *
         * @param array  $output : Text to search in where each raw is a line
         * @param string $search : Text to search for
         *
         * @return bool : True if found, false else
         */
        private function search_for_string(array $output, string $search): bool
        {
            $find = false;
            foreach ($output as $line)
            {
                $find = mb_stristr($line, $search);
                if (false !== $find)
                {
                    break;
                }
            }

            return (bool) $find;
        }
        
        
        /**
         * Method called on reception of a sms notification.
         *
         * @return array : [
         *      bool 'error' => false on success, true on error
         *      ?string 'error_message' => null on success, error message else
         *      array 'sms' => array [
         *          string 'at' : Recepetion date format Y-m-d H:i:s,
         *          string 'text' : SMS body,
         *          string 'origin' : SMS sender,
         *      ]
         *
         * ]
         */
        public static function reception_callback() : array
        {
            return [];
        }
    }
