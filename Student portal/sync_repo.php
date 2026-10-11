<?php
$php = PHP_BINARY;
$repoDir = __DIR__;
chdir($repoDir);

$latestCommit = trim(shell_exec("git rev-parse refs/heads/main"));
if (empty($latestCommit)) {
    $latestCommit = trim(shell_exec("git rev-parse HEAD"));
}
$treeListing = shell_exec("git ls-tree $latestCommit");

$lines = explode("\n", trim($treeListing));
$entries = [];
foreach ($lines as $line) {
    if (empty(trim($line))) continue;
    $parts = preg_split('/\s+/', $line, 4);
    if (count($parts) === 4) {
        $mode = $parts[0];
        $type = $parts[1];
        $hash = $parts[2];
        $name = $parts[3];
        if ($name !== 'Student portal') {
            $entries[$name] = "$mode $type $hash\t$name";
        }
    }
}

$tmpIndex = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'git_idx_' . uniqid();
putenv("GIT_INDEX_FILE=$tmpIndex");

shell_exec("git add -A .");
$studentPortalTree = trim(shell_exec("git write-tree"));

putenv("GIT_INDEX_FILE");
if (file_exists($tmpIndex)) {
    @unlink($tmpIndex);
}

if (empty($studentPortalTree) || strlen($studentPortalTree) !== 40) {
    echo "Error generating tree for Student portal.\n";
    exit(1);
}

$entries['Student portal'] = "040000 tree $studentPortalTree\tStudent portal";
ksort($entries);

$treeInput = implode("\n", array_values($entries)) . "\n";

$descriptors = [
    0 => ["pipe", "r"],
    1 => ["pipe", "w"],
    2 => ["pipe", "w"]
];
$proc = proc_open("git mktree", $descriptors, $pipes, $repoDir);
fwrite($pipes[0], $treeInput);
fclose($pipes[0]);
$newRootTree = trim(stream_get_contents($pipes[1]));
$err = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($proc);

if (empty($newRootTree) || strlen($newRootTree) !== 40) {
    echo "Error creating root tree: $err\n";
    exit(1);
}

$msg = !empty($argv[1]) ? $argv[1] : "Update StudentHub portal files";
$cmd = "git commit-tree $newRootTree -p $latestCommit -m " . escapeshellarg($msg);
$newCommit = trim(shell_exec($cmd));

if (empty($newCommit) || strlen($newCommit) !== 40) {
    echo "Error creating commit.\n";
    exit(1);
}

shell_exec("git update-ref refs/heads/main $newCommit");
echo "Created commit $newCommit\n";

$pushOutput = shell_exec("git push origin main 2>&1");
echo $pushOutput;
