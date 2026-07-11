# Access Guard Pattern

For actions that combine access checks and a state-changing operation, split the flow into three use cases:

* `Ensure*AccessUseCase`

    * performs access, ownership, and time-window checks
    * throws exceptions on failure
    * contains no DTOs

* `Get*ContextUseCase`

    * returns a context DTO (for example `topicId` or `page`)
    * performs no access checks

* `*UseCase`

    * performs the action itself
    * contains no HTTP knowledge
    * does not repeat access checks

Controller flow:

guard → context → action

Execute the action only for write operations (for example POST requests in forms or equivalent command-style operations).

## When NOT to create `Ensure*AccessUseCase`

Do not introduce a separate `Ensure*AccessUseCase` if all conditions below are true:

* guard logic is trivial (for example: 1–2 simple rights/ownership checks)
* guard is used by only one controller/action
* keeping guard separate would duplicate repository reads already needed in `Get*ContextUseCase`

In this case, move guard checks into `Get*ContextUseCase` and keep one preflight call in controller.

If guard logic grows (multiple branches, time windows, reusable policy, or shared usage in 2+ controllers), extract it back into dedicated `Ensure*AccessUseCase`.

## Exception Mapping

* Access denied → HTTP 403
* Not found / ownership mismatch → user-facing “Wrong data”
* Expired window → timeout message
* Validation or upload errors → user-facing validation errors

Do not register exceptions as DI services.
Exclude `Application/Exceptions` from service autoload.
