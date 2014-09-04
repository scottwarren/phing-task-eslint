# ESLint Phing Task

This project is a [Phing](http://phing.info) build tool task for running [eslint](https://www.github.com/eslint/eslint)

## Example

To use this task, add the classpath where you placed the JsHintTask.php in your build.xml file:

	<path id="project.class.path">
		<pathelement dir="dir/to/jshinttaskfile/"/>
	</path>

Then include it with a taskdef tag in your build.xml file:

	<taskdef name="eslint" classname="ESLintTask">
		<classpath refid="project.class.path"/>
	</taskdef>


You can now use the task

	<target name="eslint" description="Javascript Lint">
		<jshint haltonfailure="true" config="${basedir}/.eslintrc">
			<fileset dir="${basedir}/js">
				<include name="**/*.js"/>
			</fileset>
		</jshint>
	</target>

## Task Attributes

#### Required
_There are no required attributes._

#### Optional
 - **config** - Specifies the eslint config file.
 - **executable** - Path to eslint command.
 - **haltonfailure** - If the build should fail if any lint warnings is found.

====================

Thanks to [@martinj](https://github.com/martinj/phing-task-jshint) for the JSHint task that this is based from
