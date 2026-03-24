
use Illuminate\Support\Facades\View;
try {
    $rendered = View::make('suitezap.lawfirm::admin.escavador.index', ['escavadorConfigured' => true, 'escavadorBalance' => 0, 'fees' => [], 'certificates' => []])->render();
    echo "VIEW RENDERED SUCCESSFULLY\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
