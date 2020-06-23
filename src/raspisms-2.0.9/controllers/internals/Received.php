<?php

/*
 * This file is part of RaspiSMS.
 *
 * (c) Pierre-Lin Bonnemaison <plebwebsas@gmail.com>
 *
 * This source file is subject to the GPL-3.0 license that is bundled
 * with this source code in the file LICENSE.
 */

namespace controllers\internals;

    class Received extends StandardController
    {
        protected $model;

        /**
         * Return the list of unread messages for a user.
         *
         * @param int  $id_user  : User id
         * @param ?int $nb_entry : Number of entry to return
         * @param ?int $page     : Pagination, used to calcul offset, $nb_entry * $page
         *
         * @return array : Entrys list
         */
        public function list_unread_for_user(int $id_user, ?int $nb_entry = null, ?int $page = null)
        {
            return $this->get_model()->list_unread_for_user($id_user, $nb_entry, $nb_entry * $page);
        }

        /**
         * Create a received.
         *
         * @param $id_user : Id of user to create received for
         * @param int $id_phone : Id of the number the message was send with
         * @param $at : Reception date
         * @param $text : Text of the message
         * @param string $origin  : Number of the sender
         * @param string $status  : Status of the received message
         * @param bool   $command : Is the sms a command
         *
         * @return mixed : false on error, new received id else
         */
        public function create(int $id_user, int $id_phone, $at, string $text, string $origin, string $status = 'unread', bool $command = false)
        {
            $received = [
                'id_user' => $id_user,
                'id_phone' => $id_phone,
                'at' => $at,
                'text' => $text,
                'origin' => $origin,
                'status' => $status,
                'command' => $command,
            ];

            return $this->get_model()->insert($received);
        }

        /**
         * Update a received message for a user to mark the message as read.
         *
         * @param int $id_user     : user id
         * @param int $id_received : received id
         *
         * @return bool : false on error, true on success
         */
        public function mark_as_read_for_user(int $id_user, int $id_received): bool
        {
            $received = [
                'status' => 'read',
            ];

            return (bool) $this->get_model()->update_for_user($id_user, $id_received, $received);
        }

        /**
         * Update a received message for a user to mark the message as unread.
         *
         * @param int $id_user     : user id
         * @param int $id_received : received id
         *
         * @return bool : false on error, true on success
         */
        public function mark_as_unread_for_user(int $id_user, int $id_received): bool
        {
            $received = [
                'status' => 'unread',
            ];

            return (bool) $this->get_model()->update_for_user($id_user, $id_received, $received);
        }

        /**
         * Return number of unread messages for a user.
         *
         * @param int $id_user : User id
         *
         * @return array
         */
        public function count_unread_for_user(int $id_user)
        {
            return $this->get_model()->count_unread_for_user($id_user);
        }

        /**
         * Return x last receiveds message for a user, order by date.
         *
         * @param int $id_user  : User id
         * @param int $nb_entry : Number of receiveds messages to return
         *
         * @return array
         */
        public function get_lasts_by_date_for_user(int $id_user, int $nb_entry)
        {
            return $this->get_model()->get_lasts_by_date_for_user($id_user, $nb_entry);
        }

        /**
         * Return receiveds for an origin and a user.
         *
         * @param int    $id_user : User id
         * @param string $origin  : Number who sent the message
         *
         * @return array
         */
        public function gets_by_origin_and_user(int $id_user, string $origin)
        {
            return $this->get_model()->gets_by_origin_and_user($id_user, $origin);
        }

        /**
         * Get number of sended SMS for every date since a date for a specific user.
         *
         * @param int       $id_user : user id
         * @param \DateTime $date    : Date since which we want the messages
         *
         * @return array
         */
        public function count_by_day_since_for_user(int $id_user, $date)
        {
            $counts_by_day = $this->get_model()->count_by_day_since_for_user($id_user, $date);
            $return = [];

            foreach ($counts_by_day as $count_by_day)
            {
                $return[$count_by_day['at_ymd']] = $count_by_day['nb'];
            }

            return $return;
        }

        /**
         * Return all discussions (ie : numbers we have a message received from or sended to) for a user.
         *
         * @param int $id_user : User id
         *
         * @return array
         */
        public function get_discussions_for_user(int $id_user)
        {
            return $this->get_model()->get_discussions_for_user($id_user);
        }

        /**
         * Get SMS received since a date for a user.
         *
         * @param int $id_user : User id
         * @param $date : La date depuis laquelle on veux les SMS (au format 2014-10-25 20:10:05)
         *
         * @return array : Tableau avec tous les SMS depuis la date
         */
        public function get_since_by_date_for_user(int $id_user, $date)
        {
            return $this->get_model()->get_since_by_date_for_user($id_user, $date);
        }

        /**
         * Find messages received since a date for a certain origin and user.
         *
         * @param int $id_user : User id
         * @param $date : Date we want messages sinces
         * @param string $origin : Origin number
         *
         * @return array
         */
        public function get_since_by_date_for_origin_and_user(int $id_user, $date, string $origin)
        {
            return $this->get_model()->get_since_by_date_for_origin_and_user($id_user, $date, $origin);
        }

        /**
         * Find last received message for an origin and user.
         *
         * @param int    $id_user : User id
         * @param string $origin  : Origin number
         *
         * @return array
         */
        public function get_last_for_origin_and_user(int $id_user, string $origin)
        {
            return $this->get_model()->get_last_for_origin_and_user($id_user, $origin);
        }

        /**
         * Receive a SMS message.
         *
         * @param int $id_user  : Id of user to create sended message for
         * @param int $id_phone : Id of the phone the message was sent to
         * @param $text : Text of the message
         * @param string  $origin : Number of the sender
         * @param ?string $at     : Message reception date, if null use current date
         * @param string  $status : Status of a the sms. By default \models\Received::STATUS_UNREAD
         *
         * @return array : [
         *               bool 'error' => false if success, true else
         *               ?string 'error_message' => null if success, error message else
         *               ]
         */
        public function receive(int $id_user, int $id_phone, string $text, string $origin, ?string $at = null, string $status = \models\Received::STATUS_UNREAD): array
        {
            $return = [
                'error' => false,
                'error_message' => null,
            ];

            $at = $at ?? (new \DateTime())->format('Y-m-d H:i:s');
            $is_command = false;

            //Process the message to check plus potentially execute command and anonymize text
            $internal_command = new Command($this->bdd);
            $response = $internal_command->analyze_and_process($id_user, $text);
            if (false !== $response)
            { //Received sms is a command an we must use anonymized text
                $is_command = true;
                $text = $response;
            }

            $received_id = $this->create($id_user, $id_phone, $at, $text, $origin, $status, $is_command);
            if (!$received_id)
            {
                $return['error'] = true;
                $return['error_message'] = 'Impossible to insert the sms in database.';

                return $return;
            }

            $received = [
                'id' => $received_id,
                'at' => $at,
                'text' => $text,
                'destination' => $id_phone,
                'origin' => $origin,
            ];

            $internal_webhook = new Webhook($this->bdd);
            $internal_webhook->trigger($id_user, \models\Webhook::TYPE_RECEIVE, $received);

            $internal_user = new User($this->bdd);
            $internal_user->transfer_received($id_user, $received);

            return $return;
        }

        /**
         * Get the model for the Controller.
         *
         * @return \descartes\Model
         */
        protected function get_model(): \descartes\Model
        {
            $this->model = $this->model ?? new \models\Received($this->bdd);

            return $this->model;
        }
    }
