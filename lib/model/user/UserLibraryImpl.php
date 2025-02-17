<?php
namespace ChristianBudde\Part\model\user;

use ArrayIterator;
use ArrayObject;
use ChristianBudde\Part\BackendSingletonContainer;
use ChristianBudde\Part\controller\ajax\type_handler\TypeHandler;
use ChristianBudde\Part\controller\json\UserLibraryObjectImpl;
use ChristianBudde\Part\util\Observable;
use ChristianBudde\Part\util\Observer;


/**
 * Created by JetBrains PhpStorm.
 * User: budde
 * Date: 22/07/12
 * Time: 14:29
 */
class UserLibraryImpl implements UserLibrary, Observer, \Serializable
{
    private $container;
    private $userList = [];
    private $database;
    private $connection;
    /** @var $userListIterator ArrayIterator */
    private $userListIterator;
    /** @var  User */
    private $userLoggedIn;


    public function __construct(BackendSingletonContainer $container)
    {
        $this->container = $container;
        $this->database = $container->getDBInstance();
        $this->connection = $this->database->getConnection();
        $this->initializeLibrary();
        $this->setUpIterator();
        $this->setUpUserLoggedIn();
    }


    private function initializeLibrary()
    {
        $query = "SELECT username FROM User";
        foreach ($this->connection->query($query) as $row) {
            $user = new UserImpl($this->container, $row['username']);
            $user->attachObserver($this);
            $this->userList[$user->getUsername()] = $user;
        }
    }

    /**
     * Will list all users
     * @return User[]
     */
    public function listUsers()
    {
        $returnArray = array();
        foreach ($this->userList as $user) {
            $returnArray[] = $user;
        }
        return $returnArray;
    }

    /**
     * Will delete user. The user must be instance in library.
     * @param User $user
     * @return bool
     */
    public function deleteUser(User $user)
    {
        $parent = $user->getParent();
        if (!isset($this->userList[$user->getUsername()]) || $this->userList[$user->getUsername()] !== $user ||
            $parent == null
        ) {
            return false;
        }
        $this->connection->beginTransaction();
        $children = $this->getChildren($user);
        $success = true;
        foreach ($children as $child) {
            /** @var $child User */
            $success = $success && $child->setParent($parent);
        }
        $success = $success && $user->delete();

        if ($success) {
            $this->connection->commit();
            $this->setUpIterator();
        } else {
            $this->connection->rollBack();
        }
        return $success;
    }

    /**
     * Will create a user, the username must be unique
     * The created instance can be deleted and will be in list
     * from listUsers.
     * @param string $username
     * @param string $password
     * @param string $mail
     * @param User $parent
     * @return User | bool FALSE on failure else instance of User
     */
    public function createUser($username, $password, $mail, User $parent=null)
    {
        $user = new UserImpl($this->container, $username);
        if (!$user->setMail($mail) || !$user->setPassword($password) ) {
            return false;
        }

        if($parent != null && !$user->setParent($parent->getUsername())){
            return false;
        }

        if(!$user->create()){
            return false;
        }

        $this->userList[$user->getUsername()] = $user;
        $user->attachObserver($this);
        $this->setUpIterator();
        return $user;
    }

    /**
     * @return User | null User logged in else null if no user is logged in.
     */
    public function getUserLoggedIn()
    {

        if ($this->userLoggedIn == null) {
            return null;
        }

        if (!$this->userLoggedIn->isLoggedIn()) {
            return $this->userLoggedIn = null;
        }

        return $this->userLoggedIn;
    }

    public function onChange(Observable $subject, $changeType)
    {
        switch ($changeType) {
            case User::EVENT_DELETE:
                if ($subject instanceof User) {
                    /** @var $subject User */
                    if (isset($this->userList[$subject->getUsername()]) && $this->userList[$subject->getUsername()] === $subject) {
                        unset($this->userList[$subject->getUsername()]);
                    }
                }
                break;
            case User::EVENT_USERNAME_UPDATE:
                foreach ($this->userList as $key => $user) {
                    if ($subject === $user) {
                        unset($this->userList[$key]);
                    }
                }
                /** @var $subject User */
                $this->userList[$subject->getUsername()] = $subject;
                break;
            case User::EVENT_LOGIN:
                $this->userLoggedIn = $subject;
                unset($_SESSION['model-user-library-session-token']);

        }
    }

    /**
     * @param string $username
     * @return User | null User if username is found, else null
     */
    public function getUser($username)
    {
        return isset($this->userList[$username]) ? $this->userList[$username] : null;
    }

    /**
     *
     * Parameter must be an instance provided from Library.
     * @param User $user
     * @return User | null Will return User if the user provided has parent, else null.
     */
    public function getParent(User $user)
    {
        return $this->getUser($user->getParent());
    }

    /**
     * Input must be instance of User and an instance provided by the library.
     * @param User $user
     * @return array Array containing children User instances. Empty array on no children or input not valid.
     */
    public function getChildren(User $user)
    {
        $returnArray = array();
        foreach ($this->userList as $u) {
            /** @var $u User */
            if ($u->getParent() == $user->getUsername()) {
                $returnArray[] = $u;
                $returnArray = array_merge($returnArray, $this->getChildren($u));
            }
        }
        return $returnArray;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return User
     */
    public function current()
    {
        return $this->userListIterator->current();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->userListIterator->next();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->userListIterator->key();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return $this->userListIterator->valid();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->userListIterator->rewind();
    }

    private function setUpIterator()
    {
        $arrayObject = new ArrayObject($this->listUsers());
        $this->userListIterator = $arrayObject->getIterator();
    }

    /**
     * Serializes the object to an instance of JSONObject.
     * @return Object
     */
    public function jsonObjectSerialize()
    {
        return new UserLibraryObjectImpl($this);
    }


    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return $this->jsonObjectSerialize()->jsonSerialize();
    }

    /**
     * Returns the current user settings token.
     * If no user is logged in, the token will be null.
     *
     * @return string
     */
    public function getUserSessionToken()
    {
        if (($u = $this->getUserLoggedIn()) == null) {
            return null;
        }

        return isset($_SESSION['model-user-library-session-token']) ? $_SESSION['model-user-library-session-token'] : $_SESSION['model-user-library-session-token'] = $u->getUserToken();
    }

    /**
     * Will compare the tokens. If no user is logged in all tokens are equally valid. I.e. valid.
     * @param string $token
     * @return string
     */
    public function verifyUserSessionToken($token)
    {
        return ($t = $this->getUserSessionToken()) == null || $t == $token;
    }

    private function setUpUserLoggedIn()
    {
        foreach ($this->userList as $user) {
            /** @var $user User */
            if ($user->isLoggedIn()) {
                $this->userLoggedIn = $user;
                return;
            }
        }
    }

    /**
     * @return TypeHandler
     */
    public function generateTypeHandler()
    {
        return $this->container->getTypeHandlerLibraryInstance()->getUserLibraryTypeHandlerInstance($this);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize([$this->container, $this->userList, $this->database, $this->connection]);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        $array = unserialize($serialized);
        $this->container = $array[0];
        $this->userList = $array[1];
        $this->database = $array[2];
        $this->connection = $array[3];
    }
}
