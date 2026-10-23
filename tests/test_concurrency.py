"""Concurrent course-content rendering should not leak render state between users."""
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
import sys
import threading
import unittest

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "scripts"))
from build_course_content import markdown


class ConcurrentRenderingTests(unittest.TestCase):
    def test_reentrant_markdown_rendering_with_distinct_course_inputs(self):
        workers = 12
        gate = threading.Barrier(workers)
        sources = [f"# Unit {i}\n- Topic {i}\n- Practice\n\nText <script>{i}</script>."
                   for i in range(workers)]
        expected = [markdown(source) for source in sources]

        def render(i):
            gate.wait(timeout=15)
            return markdown(sources[i])

        with ThreadPoolExecutor(max_workers=workers) as pool:
            results = list(pool.map(render, range(workers)))
        self.assertEqual(results, expected)
        self.assertTrue(all("<script>" not in x for x in results))
        self.assertTrue(all(f"Unit {i}" in results[i] for i in range(workers)))


if __name__ == "__main__":
    unittest.main()
