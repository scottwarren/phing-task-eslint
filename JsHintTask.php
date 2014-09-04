<?php
/*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
* "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
* LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
* A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
* OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
* SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
* LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
* DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
* THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
* OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

require_once 'phing/Task.php';
require_once 'phing/util/DataStore.php';

/**
* A Javascript lint task using ESLint (https://www.github.com/eslint/eslint)
* This class is based on Martin Jonsson's JslintTask (https://github.com/martinj/phing-task-jshint)
*
* @author Scott Warren <https://www.github.com/scottwarren>
* @version 1.0.0
*/
class ESLintTask extends Task
{
    private $file          = null;
    private $config        = null;
    private $haltOnFailure = false;
    private $hasErrors     = false;
    private $filesets      = array();
    private $executable    = 'eslint';

    /**
     * Property name to set with return value from exec call.
     *
     * @var string
     */
    protected $returnProperty;

    public function setFile(PhingFile $file)
    {
        $this->file = $file;
    }

    public function setExecutable($executable)
    {
        $this->executable = $executable;
    }

    public function setConfig(PhingFile $config)
    {
        $this->config = $config;
    }

    public function setHaltOnFailure($haltOnFailure)
    {
        $this->haltOnFailure = $haltOnFailure;
    }

    /**
     * Create fileset for this task
     */
    public function createFileSet()
    {
        $num = array_push($this->filesets, new FileSet());
        return $this->filesets[$num-1];
    }

    public function main()
    {
        $this->hasErrors = false;

        if (!isset($this->file) and count($this->filesets) == 0) {
            throw new BuildException("Missing either a nested fileset or attribute 'file' set");
        }

        exec('"' . $this->executable . '" -v', $output, $ret);
        if ($ret !== 0) {
            throw new BuildException('ESLint command not found');
        }

        if ($this->file instanceof PhingFile && $this->isJsFile($this->file->getPath())) {
            $this->lint($this->file->getPath());
        } else { // process filesets
            $project = $this->getProject();

            foreach ($this->filesets as $fs) {
                $ds = $fs->getDirectoryScanner($project);
                $files = $ds->getIncludedFiles();
                $dir = $fs->getDir($this->project)->getPath();

                foreach ($files as $file) {
                    if ($this->isJsFile($dir.DIRECTORY_SEPARATOR.$file)) {
                        $this->lint($dir.DIRECTORY_SEPARATOR.$file);
                    }
                }
            }
        }

        if ($this->haltOnFailure && $this->hasErrors) {
            throw new BuildException('Syntax error(s) in JS files');
        }

        if ($this->returnProperty && $this->hasErrors) {
            $this->project->setProperty($this->returnProperty, 1);
        }

        if ($this->returnProperty && !$this->hasErrors) {
            $this->project->setProperty($this->returnProperty, 0);
        }

    }

    /**
     * The name of property to set to return value from exec() call.
     *
     * @param string $prop Property name
     *
     * @return void
     */
    public function setReturnProperty($prop)
    {
        $this->returnProperty = $prop;
    }

    public function lint($file)
    {
        $command = '"' . $this->executable . '"' . ' "' . $file . '" ';
        if (isset($this->config)) {
            $command .= '--config ' . escapeshellarg($this->config->getPath()) . ' ';
        }

        if (!file_exists($file)) {
            throw new BuildException('File not found: ' . $file);
        }

        if (!is_readable($file)) {
            throw new BuildException('Permission denied: ' . $file);
        }

        $messages = array();
        $summary = exec($command, $messages);

        // filter each value in the array and removes it if it equates to false (eg empty values)
        $messages = array_filter($messages);

        foreach ($messages as $line) {
            $this->log($line);
            if (preg_match('/error/', $line, $matches)) {
                $this->hasErrors = true;
            }
        }
    }
    /**
     * Return true if an uncompressed javascript file.
     * @param string $file
     *   file name
     */
    public static function isJsFile($file)
    {
        return self::endsWith($file, '.js') && !self::endsWith($file, '.min.js');
    }

    /**
     * Check if the given string $haystack ends with $needle.
     *
     * @param string $haystack
     *   string to search in
     * @param string $needle
     *   search for this string
     */
    public static function endsWith($haystack, $needle)
    {
        $haystack = strtolower($haystack);
        $needle = strtolower($needle);
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
}
