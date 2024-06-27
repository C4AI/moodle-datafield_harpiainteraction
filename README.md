# Moodle plugin: HarpIA Interaction

HarpIA Interaction is a Moodle plugin that adds a field to Database activities
where users can interact with an external language model. 

The main goal of this plugin is to implement an LLM evaluation system:
- teachers set up a language model evaluation task in a given context;
- students (or invited evaluators) read the instructions,
    send a message to the language model and evaluate its output
    by filling in the fields set by teachers.

The plugin can be used in any situation in which a number of students
have to send a request to a language model and perform some actions
based on its output.

### Dependencies

- Moodle &geq; 4.0.3;
- [HarpIA Ajax](../../../moodle-local_harpiaajax) plugin.
