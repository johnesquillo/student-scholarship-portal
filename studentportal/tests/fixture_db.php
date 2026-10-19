<?php
// Test-only mysqli-shaped adapter. Production always uses MySQL via www/db.php.
class TestResult
{
    private $rows;
    private $index = 0;
    public $num_rows;
    function __construct($rows) { $this->rows = $rows; $this->num_rows = count($rows); }
    function fetch_assoc() { return $this->rows[$this->index++] ?? null; }
    function fetch_all($mode) { return $this->rows; }
}
class TestStatement
{
    private $statement;
    private $arguments = [];
    private $types = '';
    private $result;
    public $affected_rows = 0;
    function __construct($statement) { $this->statement = $statement; }
    function bind_param($types, &...$arguments) { $this->types = $types; $this->arguments = &$arguments; }
    function execute()
    {
        foreach ($this->arguments as $index => $value) {
            $this->statement->bindValue($index + 1, $value, $this->types[$index] === 'i' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        try {
            $this->statement->execute();
        } catch (PDOException $exception) {
            throw new mysqli_sql_exception($exception->getMessage(), strpos($exception->getMessage(), 'UNIQUE constraint') !== false ? 1062 : 1);
        }
        $this->affected_rows = $this->statement->rowCount();
        $this->result = new TestResult($this->statement->fetchAll(PDO::FETCH_ASSOC));
        return true;
    }
    function get_result() { return $this->result; }
    function close() { $this->statement->closeCursor(); }
}
class TestConnection
{
    private $pdo;
    function __construct()
    {
        $this->pdo = new PDO('sqlite:' . dirname(__DIR__) . '/test.sqlite');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    function prepare($sql)
    {
        $sql = str_replace(['CURDATE()', ' FOR UPDATE'], ["date('now')", ''], $sql);
        return new TestStatement($this->pdo->prepare($sql));
    }
    function query($sql) { $statement = $this->prepare($sql); $statement->execute(); return $statement->get_result(); }
    function begin_transaction() { $this->pdo->beginTransaction(); }
    function commit() { $this->pdo->commit(); }
    function rollback() { $this->pdo->rollBack(); }
    function close() {}
}
$conn = new TestConnection();
